<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grantee_upload_batches', function (Blueprint $table) {
            $table->json('uploaded_filenames')->nullable()->after('original_file_name');
        });

        Schema::table('staged_grantee_records', function (Blueprint $table) {
            $table->string('source_pdf_name')->nullable()->after('row_number');
            $table->string('scholarship_program')->nullable()->after('scholarship_name');
            $table->string('raw_agency_name', 500)->nullable()->after('student_name');
            $table->string('enrollment_status', 50)->nullable()->after('verification_status');
            $table->text('remarks')->nullable()->after('conflict_reason');

            $table->index('scholarship_program');
            $table->index('enrollment_status');
        });
    }

    public function down(): void
    {
        Schema::table('staged_grantee_records', function (Blueprint $table) {
            $table->dropIndex(['scholarship_program']);
            $table->dropIndex(['enrollment_status']);
            $table->dropColumn([
                'source_pdf_name',
                'scholarship_program',
                'raw_agency_name',
                'enrollment_status',
                'remarks',
            ]);
        });

        Schema::table('grantee_upload_batches', function (Blueprint $table) {
            $table->dropColumn('uploaded_filenames');
        });
    }
};
