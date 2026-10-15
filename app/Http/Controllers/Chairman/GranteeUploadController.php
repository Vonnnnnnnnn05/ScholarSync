<?php

namespace App\Http\Controllers\Chairman;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\ScholarshipProgram;
use App\Services\GranteeFileParserService;
use App\Services\GranteeHeaderMapperService;
use App\Services\GranteeStagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GranteeUploadController extends Controller
{
    public function __construct(
        protected GranteeFileParserService $parserService,
        protected GranteeHeaderMapperService $mapperService,
        protected GranteeStagingService $stagingService
    ) {}

    /**
     * Show the file upload form.
     */
    public function create(): View
    {
        return view('chairman.grantees.upload', [
            'agencies' => Agency::query()->orderBy('agency_name')->get(),
            'campuses' => \App\Models\Campus::query()->where('is_active', true)->orderBy('name')->get(),
            'currentYear' => (int) date('Y'),
        ]);
    }

    /**
     * Handle AJAX file parsing to return preview data.
     */
    public function parse(Request $request): JsonResponse
    {
        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $uploadedFiles = $request->file('files');
        } elseif ($request->hasFile('grantee_files')) {
            $uploadedFiles = $request->file('grantee_files');
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        } elseif ($request->hasFile('grantee_file')) {
            $uploadedFiles = [$request->file('grantee_file')];
        }

        if (empty($uploadedFiles)) {
            return response()->json(['success' => false, 'error' => 'No files uploaded.'], 400);
        }

        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        if (count($uploadedFiles) === 1) {
            $parsed = $this->parserService->parse($uploadedFiles[0]);
        } else {
            $parsed = $this->parserService->parseFiles($uploadedFiles);
        }

        $previewData = $this->mapperService->validateAndStructure($parsed);

        // Auto-resolve students and campuses against pre-seeded RegistrarStudent roster
        $allSis = \App\Models\RegistrarStudent::where('student_id_number', '!=', 'Student Number')->get();
        $bySurname = [];
        foreach ($allSis as $sis) {
            $parts = explode(',', $sis->student_name);
            $last = strtoupper(trim($parts[0]));
            $bySurname[$last][] = $sis;
        }

        $allRows = $previewData['all_rows'] ?? ($parsed['rows'] ?? []);
        $categoryCounts = [
            'TES-UniFAST' => 0,
            'TDP' => 0,
            'CMSP' => 0,
        ];
        $matchedCount = 0;

        foreach ($allRows as &$row) {
            $p = $row['scholarship_program'] ?? $row['scholarship_name'] ?? 'TES-UniFAST';
            if (str_contains($p, 'TES')) {
                $categoryCounts['TES-UniFAST']++;
            } elseif (str_contains($p, 'TDP') || str_contains($p, 'Tulong')) {
                $categoryCounts['TDP']++;
            } elseif (str_contains($p, 'CMSP') || str_contains($p, 'Merit')) {
                $categoryCounts['CMSP']++;
            } else {
                $categoryCounts[$p] = ($categoryCounts[$p] ?? 0) + 1;
            }

            $last = strtoupper(trim($row['last_name'] ?? ''));
            $first = strtoupper(trim($row['first_name'] ?? ''));

            $candidates = $bySurname[$last] ?? [];
            $bestMatch = null;

            foreach ($candidates as $cand) {
                $candParts = explode(',', $cand->student_name);
                $candFirst = strtoupper(trim($candParts[1] ?? ''));
                $candFirstClean = trim(preg_replace('/\s+[A-Z]\.?$/', '', $candFirst));

                if ($candFirstClean === $first || str_starts_with($first, $candFirstClean) || str_starts_with($candFirstClean, $first)) {
                    $bestMatch = $cand;
                    break;
                }
            }

            if ($bestMatch) {
                $matchedCount++;
                $row['student_id'] = $bestMatch->student_id_number;
                $row['campus'] = $bestMatch->campus ?? 'ACCESS Campus';
                $row['campus_id'] = $bestMatch->campus_id;
                $row['program'] = $bestMatch->course;
                $row['matched_sis_student_id'] = $bestMatch->id;

                if ($bestMatch->enrollment_status === 'enrolled' && $bestMatch->cor_printed) {
                    $row['enrollment_status'] = 'ENROLLED';
                } elseif ($bestMatch->enrollment_status === 'enrolled' && !$bestMatch->cor_printed) {
                    $row['enrollment_status'] = 'NO_COR_PRINTED';
                } else {
                    $row['enrollment_status'] = 'UNENROLLED';
                }
                $row['verification_status'] = 'GREEN_VERIFIED';
                $row['confidence_score'] = 1.0;
                $row['remarks'] = "Matched in SIS roster ({$row['campus']})";
            } else {
                $row['student_id'] = $row['student_id'] ?? null;
                $row['campus'] = $row['campus'] ?? null;
                $row['campus_id'] = $row['campus_id'] ?? null;
                $row['program'] = $row['program'] ?? null;
                $row['enrollment_status'] = $row['enrollment_status'] ?? null;
                $row['verification_status'] = $row['verification_status'] ?? 'PENDING';
            }
        }
        unset($row);

        $previewData['all_rows'] = $allRows;
        $previewData['preview_rows'] = array_slice($allRows, 0, 50);
        $previewData['category_counts'] = $categoryCounts;
        $previewData['matched_count'] = $matchedCount;
        $previewData['unmatched_count'] = count($allRows) - $matchedCount;

        // Store temporary paths for final submission
        $tempPaths = [];
        $origNames = [];
        foreach ($uploadedFiles as $f) {
            $tempPaths[] = $f->store('grantees/temp', 'local');
            $origNames[] = $f->getClientOriginalName();
        }

        session([
            'grantee_upload_temp' => [
                'temp_paths' => $tempPaths,
                'temp_path' => $tempPaths[0] ?? null,
                'original_names' => $origNames,
                'original_name' => implode(', ', $origNames),
                'file_type' => $previewData['file_type'] ?? 'pdf',
                'raw_headers' => $previewData['original_headers'] ?? [],
                'all_rows' => $previewData['all_rows'],
                'total_rows' => count($previewData['all_rows']),
                'category_counts' => $categoryCounts,
                'matched_count' => $matchedCount,
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => $previewData,
        ]);
    }

    /**
     * Confirm and stage parsed grantee rows into the database.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'agency_name' => ['nullable', 'string', 'max:255'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'apply_campus_to_all' => ['nullable', 'boolean'],
            'scholarship_name' => ['nullable', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:50'],
            'semester' => ['nullable', 'string', 'max:50'],
            'row_campuses' => ['nullable', 'array'],
            'row_campuses.*' => ['nullable', 'integer', 'exists:campuses,id'],
        ]);

        $sessionData = session('grantee_upload_temp');
        if (!$sessionData) {
            return redirect()->route('chairman.grantees.upload')
                ->withErrors(['file' => 'Upload session expired. Please upload and parse your file again.']);
        }

        $tempPaths = $sessionData['temp_paths'] ?? (isset($sessionData['temp_path']) ? [$sessionData['temp_path']] : []);
        $origNames = $sessionData['original_names'] ?? (isset($sessionData['original_name']) ? [$sessionData['original_name']] : []);

        if (empty($tempPaths)) {
            return redirect()->route('chairman.grantees.upload')
                ->withErrors(['file' => 'Uploaded files could not be found. Please re-upload.']);
        }

        $files = [];
        $fullPaths = [];
        foreach ($tempPaths as $idx => $tPath) {
            if (Storage::disk('local')->exists($tPath)) {
                $fPath = Storage::disk('local')->path($tPath);
                $fullPaths[] = $fPath;
                $files[] = new UploadedFile(
                    $fPath,
                    $origNames[$idx] ?? basename($fPath),
                    null,
                    null,
                    true
                );
            }
        }

        if (empty($files)) {
            return redirect()->route('chairman.grantees.upload')
                ->withErrors(['file' => 'Uploaded file could not be found on server. Please re-upload.']);
        }

        $currentYear = (int) date('Y');
        $defaultAy = "AY {$currentYear}-" . ($currentYear + 1);
        $agencyName = $request->filled('agency_name') ? $request->string('agency_name')->toString() : 'CHED-UniFAST';
        $academicYear = $request->filled('academic_year') ? $request->string('academic_year')->toString() : $defaultAy;
        $semester = $request->filled('semester') ? $request->string('semester')->toString() : '1st Semester';
        $fallbackCampusId = $request->filled('campus_id') ? $request->integer('campus_id') : null;
        $applyCampusToAll = $request->boolean('apply_campus_to_all', true);
        $rowCampuses = $request->input('row_campuses', []);

        $rows = $sessionData['all_rows'] ?? [];
        if (!empty($rowCampuses) || !empty($fallbackCampusId)) {
            $campusesById = \App\Models\Campus::all()->keyBy('id');
            $targetCampusName = $fallbackCampusId ? $campusesById->get($fallbackCampusId)?->name : null;

            foreach ($rows as &$r) {
                $rNum = $r['row_number'] ?? null;
                if ($rNum && !empty($rowCampuses[$rNum])) {
                    $cId = (int) $rowCampuses[$rNum];
                    $r['campus_id'] = $cId;
                    $r['campus'] = $campusesById->get($cId)?->name ?? $r['campus'];
                } elseif (!empty($fallbackCampusId)) {
                    if ($applyCampusToAll || empty($r['campus_id'])) {
                        $r['campus_id'] = $fallbackCampusId;
                        $r['campus'] = $targetCampusName ?? $r['campus'];
                    }
                }
            }
            unset($r);
        }

        $batch = $this->stagingService->stageBatch(
            $request->user(),
            count($files) === 1 ? $files[0] : $files,
            [
                'agency_name' => $agencyName,
                'campus_id' => $fallbackCampusId,
                'scholarship_name' => $request->filled('scholarship_name')
                    ? $request->string('scholarship_name')->toString()
                    : 'Auto-Detected',
                'academic_year' => $academicYear,
                'semester' => $semester,
                'file_type' => $sessionData['file_type'],
                'raw_headers' => $sessionData['raw_headers'],
                'rows' => $rows,
            ]
        );

        // Clean up session and temporary files
        session()->forget('grantee_upload_temp');
        foreach ($tempPaths as $tPath) {
            Storage::disk('local')->delete($tPath);
        }

        return redirect()->route('chairman.grantees.show', $batch)
            ->with('success', "Batch #{$batch->id} successfully staged ({$batch->total_rows} records ready for verification).");
    }
}
