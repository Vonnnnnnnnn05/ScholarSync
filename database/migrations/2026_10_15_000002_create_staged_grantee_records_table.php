<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staged_grantee_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('grantee_upload_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_payload')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('student_name', 500)->nullable();
            $table->string('student_id', 50)->nullable();
            $table->string('campus')->nullable();
            $table->foreignId('campus_id')->nullable()->constrained('campuses');
            $table->string('program')->nullable();
            $table->string('scholarship_name')->nullable();
            $table->json('missing_fields_flag')->nullable();
            $table->enum('verification_status', ['PENDING', 'GREEN_VERIFIED', 'AMBER_CONFLICT', 'RED_UNMATCHED'])->default('PENDING');
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->unsignedBigInteger('matched_sis_student_id')->nullable();
            $table->unsignedBigInteger('matched_student_id')->nullable();
            $table->string('conflict_reason')->nullable();
            $table->json('candidate_matches')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->foreign('matched_sis_student_id')->references('id')->on('registrar_students')->nullOnDelete();
            $table->foreign('matched_student_id')->references('id')->on('students')->nullOnDelete();

            $table->index(['batch_id', 'verification_status']);
            $table->index('student_id');
            $table->index('matched_sis_student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staged_grantee_records');
    }
};
