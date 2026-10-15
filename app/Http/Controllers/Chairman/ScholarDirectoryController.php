<?php

namespace App\Http\Controllers\Chairman;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\MasterlistRecord;
use App\Models\StagedGranteeRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScholarDirectoryController extends Controller
{
    /**
     * Display the verified scholarship grantees directory.
     */
    public function index(Request $request): View
    {
        $campusId = $request->query('campus');
        $program = $request->query('program');
        $search = trim((string) $request->query('search'));

        // Query green-verified staged records (primary source of grantee ingestion)
        $stagedQuery = StagedGranteeRecord::query()
            ->where('verification_status', 'GREEN_VERIFIED')
            ->with(['batch', 'campus']);

        if (!empty($campusId) && $campusId !== 'all') {
            $stagedQuery->where(function ($q) use ($campusId) {
                $q->where('campus_id', $campusId)
                  ->orWhere('campus', $campusId)
                  ->orWhereHas('campus', fn ($cq) => $cq->where('name', $campusId));
            });
        }

        if (!empty($program) && $program !== 'all') {
            $stagedQuery->where(function ($q) use ($program) {
                $q->where('scholarship_program', $program)
                  ->orWhere('scholarship_name', $program);
            });
        }

        if (!empty($search)) {
            $stagedQuery->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $totalStaged = StagedGranteeRecord::where('verification_status', 'GREEN_VERIFIED')->count();

        // If no staged records exist but legacy approved masterlist records do, fallback gracefully
        if ($totalStaged === 0 && MasterlistRecord::where('chairman_status', 'approved')->exists()) {
            $legacyQuery = MasterlistRecord::query()
                ->where('chairman_status', 'approved')
                ->with(['campus', 'masterlist']);

            if (!empty($campusId) && $campusId !== 'all') {
                $legacyQuery->where('campus_id', $campusId);
            }

            if (!empty($program) && $program !== 'all') {
                $legacyQuery->where('scholarship_program', $program);
            }

            if (!empty($search)) {
                $legacyQuery->where(function ($q) use ($search) {
                    $q->where('student_name', 'like', "%{$search}%")
                      ->orWhere('student_id_number', 'like', "%{$search}%");
                });
            }

            $scholars = $legacyQuery->latest()->paginate(25)->withQueryString();
            $totalCount = MasterlistRecord::where('chairman_status', 'approved')->count();
            $tesCount = MasterlistRecord::where('chairman_status', 'approved')->where('scholarship_program', 'like', '%TES%')->count();
            $tdpCount = MasterlistRecord::where('chairman_status', 'approved')->where('scholarship_program', 'like', '%TDP%')->count();
            $cmspCount = MasterlistRecord::where('chairman_status', 'approved')->where('scholarship_program', 'like', '%CMSP%')->count();
            $isLegacy = true;
        } else {
            $scholars = $stagedQuery->latest()->paginate(25)->withQueryString();
            $totalCount = $totalStaged;
            $tesCount = StagedGranteeRecord::where('verification_status', 'GREEN_VERIFIED')
                ->where(fn ($q) => $q->where('scholarship_program', 'like', '%TES%')->orWhere('scholarship_name', 'like', '%TES%'))
                ->count();
            $tdpCount = StagedGranteeRecord::where('verification_status', 'GREEN_VERIFIED')
                ->where(fn ($q) => $q->where('scholarship_program', 'like', '%TDP%')->orWhere('scholarship_name', 'like', '%TDP%')->orWhere('scholarship_program', 'like', '%Tulong%'))
                ->count();
            $cmspCount = StagedGranteeRecord::where('verification_status', 'GREEN_VERIFIED')
                ->where(fn ($q) => $q->where('scholarship_program', 'like', '%CMSP%')->orWhere('scholarship_name', 'like', '%CMSP%')->orWhere('scholarship_program', 'like', '%Merit%'))
                ->count();
            $isLegacy = false;
        }

        $campuses = Campus::query()->orderBy('name')->get();
        $knownPrograms = ['TES-UniFAST', 'TDP', 'CMSP'];

        return view('chairman.scholars.index', [
            'scholars' => $scholars,
            'campuses' => $campuses,
            'programs' => $knownPrograms,
            'selectedCampus' => $campusId ?: 'all',
            'selectedProgram' => $program ?: 'all',
            'search' => $search,
            'totalCount' => $totalCount,
            'tesCount' => $tesCount,
            'tdpCount' => $tdpCount,
            'cmspCount' => $cmspCount,
            'isLegacy' => $isLegacy,
        ]);
    }
}
