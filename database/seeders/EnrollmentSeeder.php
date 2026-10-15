<?php

namespace Database\Seeders;

use App\Http\Controllers\Registrar\EnrolledStudentController;
use App\Models\Campus;
use App\Models\RegistrarStudent;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = base_path('Sample_Files/Access.xlsx');
        if (!file_exists($filePath)) {
            $this->command?->warn("Access.xlsx not found at {$filePath}, skipping EnrollmentSeeder.");
            return;
        }

        $accessCampus = Campus::where('name', 'like', '%ACCESS%')->first();
        if (!$accessCampus) {
            $accessCampus = Campus::firstOrCreate(
                ['name' => 'ACCESS Campus'],
                ['code' => 'access', 'is_active' => true]
            );
        }
        $campusId = $accessCampus->id;
        $campusName = $accessCampus->name;

        $file = new UploadedFile(
            $filePath,
            'Access.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $controller = new EnrolledStudentController();
        $parsed = $controller->extractRows($file, $campusId);
        $records = $parsed['rows'];

        $this->command?->info("Importing " . count($records) . " enrolled student records from Access.xlsx into {$campusName}...");

        $now = now();
        $chunks = array_chunk($records, 500);
        $imported = 0;

        DB::transaction(function () use ($chunks, $campusId, $campusName, $now, &$imported) {
            foreach ($chunks as $chunk) {
                $batchData = [];
                foreach ($chunk as $row) {
                    $idNumber = trim((string) ($row['student_id_number'] ?? ''));
                    $name = trim((string) ($row['student_name'] ?? ''));

                    if ($idNumber === '' || $name === '') {
                        continue;
                    }

                    $benchmarkIds = ['24890', '25197'];
                    if (in_array($idNumber, $benchmarkIds, true)) {
                        $status = 'enrolled';
                        $corPrinted = true;
                    } else {
                        $seed = abs(crc32($idNumber) % 100);
                        if ($seed < 90) {
                            $status = 'enrolled';
                            $corPrinted = true;
                        } elseif ($seed < 98) {
                            $status = 'enrolled';
                            $corPrinted = false; // NO_COR_PRINTED
                        } else {
                            $status = 'not_enrolled'; // UNENROLLED
                            $corPrinted = false;
                        }
                    }

                    $batchData[] = [
                        'student_id_number' => $idNumber,
                        'campus_id' => $campusId,
                        'student_name' => $name,
                        'course' => $row['course'] ?? null,
                        'year_level' => $row['year_level'] ?? null,
                        'campus' => $campusName,
                        'enrollment_status' => $status,
                        'cor_printed' => $corPrinted,
                        'academic_year' => $row['academic_year'] ?? '2026-2027',
                        'semester' => $row['semester'] ?? '1st Semester',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($batchData)) {
                    RegistrarStudent::upsert(
                        $batchData,
                        ['student_id_number'],
                        ['campus_id', 'student_name', 'course', 'year_level', 'campus', 'enrollment_status', 'cor_printed', 'academic_year', 'semester', 'updated_at']
                    );
                    $imported += count($batchData);
                }
            }
        });

        $this->command?->info("Successfully seeded {$imported} enrolled student records for {$campusName}!");
    }
}
