<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grantee_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('agency_name')->nullable();
            $table->string('scholarship_name')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->string('semester', 20)->nullable();
            $table->string('original_file_name');
            $table->string('stored_file_path', 500);
            $table->enum('file_type', ['csv', 'xlsx', 'pdf']);
            $table->json('raw_headers')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('green_count')->default(0);
            $table->unsignedInteger('amber_count')->default(0);
            $table->unsignedInteger('red_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->enum('status', ['staged', 'verifying', 'verified', 'committed', 'failed'])->default('staged');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->foreignId('committed_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grantee_upload_batches');
    }
};
