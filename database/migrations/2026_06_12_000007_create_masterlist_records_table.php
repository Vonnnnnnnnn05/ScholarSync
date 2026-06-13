<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('masterlist_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masterlist_id')->constrained('scholarship_masterlists')->cascadeOnDelete();
            $table->foreignId('matched_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('student_id_number')->nullable()->index();
            $table->string('student_name')->nullable();
            $table->string('scholarship_program')->nullable()->index();
            $table->string('fund_source')->nullable()->index();
            $table->string('verification_status')->default('pending')->index();
            $table->string('coordinator_status')->default('pending')->index();
            $table->string('chairman_status')->default('pending')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('masterlist_records');
    }
};
