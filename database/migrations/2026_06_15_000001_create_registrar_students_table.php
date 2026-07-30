<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrar_students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id_number')->unique();
            $table->string('student_name');
            $table->string('course')->nullable();
            $table->string('year_level')->nullable();
            $table->string('campus')->nullable();
            $table->string('enrollment_status')->default('enrolled')->index();
            $table->string('academic_year')->nullable()->index();
            $table->string('semester')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrar_students');
    }
};
