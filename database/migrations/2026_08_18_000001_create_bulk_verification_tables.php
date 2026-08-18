<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masterlist_verification_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('masterlist_campus_batch_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('queued')->index();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('completed_chunks')->default(0);
            $table->unsignedInteger('qualified_count')->default(0);
            $table->unsignedInteger('not_qualified_count')->default(0);
            $table->unsignedInteger('needs_review_count')->default(0);
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('masterlist_records', function (Blueprint $table): void {
            $table->string('automatic_enrollment_status')->default('pending')->index();
            $table->string('automatic_cor_status')->default('pending')->index();
            $table->string('automatic_qualification_status')->default('pending')->index();
            $table->string('final_enrollment_status')->default('pending')->index();
            $table->string('final_cor_status')->default('pending')->index();
            $table->string('final_qualification_status')->default('pending')->index();
            $table->string('match_status')->default('pending')->index();
            $table->timestamp('automatic_verified_at')->nullable();
            $table->text('automatic_result_message')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
        });

        Schema::create('masterlist_record_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('masterlist_verification_run_id');
            $table->foreign('masterlist_verification_run_id', 'ml_record_verifications_run_fk')
                ->references('id')->on('masterlist_verification_runs')->cascadeOnDelete();
            $table->foreignId('masterlist_record_id');
            $table->foreign('masterlist_record_id', 'ml_record_verifications_record_fk')
                ->references('id')->on('masterlist_records')->cascadeOnDelete();
            $table->json('original_data');
            $table->json('matched_data')->nullable();
            $table->string('match_status');
            $table->string('enrollment_status');
            $table->string('cor_status');
            $table->string('qualification_status');
            $table->text('result_message')->nullable();
            $table->string('service_version')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
            $table->unique(['masterlist_verification_run_id', 'masterlist_record_id'], 'verification_run_record_unique');
        });

        Schema::create('masterlist_registrar_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('masterlist_record_id');
            $table->foreign('masterlist_record_id', 'ml_registrar_resolutions_record_fk')
                ->references('id')->on('masterlist_records')->cascadeOnDelete();
            $table->foreignId('registrar_id');
            $table->foreign('registrar_id', 'ml_registrar_resolutions_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->json('previous_results');
            $table->json('new_results');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masterlist_registrar_resolutions');
        Schema::dropIfExists('masterlist_record_verifications');
        Schema::table('masterlist_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn([
                'automatic_enrollment_status', 'automatic_cor_status', 'automatic_qualification_status',
                'final_enrollment_status', 'final_cor_status', 'final_qualification_status',
                'match_status', 'automatic_verified_at', 'automatic_result_message', 'resolved_at',
            ]);
        });
        Schema::dropIfExists('masterlist_verification_runs');
    }
};
