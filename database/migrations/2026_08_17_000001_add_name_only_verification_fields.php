<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrar_students', function (Blueprint $table): void {
            $table->boolean('cor_printed')->default(false)->after('enrollment_status');
        });

        Schema::table('scholarship_masterlists', function (Blueprint $table): void {
            $table->unsignedInteger('no_cor_printed_count')->default(0)->after('enrolled_count');
        });

        Schema::table('masterlist_records', function (Blueprint $table): void {
            $table->foreignId('registrar_student_id')->nullable()->after('masterlist_id')->constrained('registrar_students')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('masterlist_records', fn (Blueprint $table) => $table->dropConstrainedForeignId('registrar_student_id'));
        Schema::table('scholarship_masterlists', fn (Blueprint $table) => $table->dropColumn('no_cor_printed_count'));
        Schema::table('registrar_students', fn (Blueprint $table) => $table->dropColumn('cor_printed'));
    }
};
