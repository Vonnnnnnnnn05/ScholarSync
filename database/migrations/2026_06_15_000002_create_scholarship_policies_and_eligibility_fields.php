<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scholarship_program_id')->nullable()->constrained('scholarship_programs')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('eligibility_requirements')->nullable();
            $table->text('documentary_requirements')->nullable();
            $table->date('deadline')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('published')->index();
            $table->timestamps();
        });

        Schema::table('scholarship_masterlists', function (Blueprint $table) {
            if (! Schema::hasColumn('scholarship_masterlists', 'qualified_count')) {
                $table->unsignedInteger('qualified_count')->default(0)->after('invalid_count');
            }

            if (! Schema::hasColumn('scholarship_masterlists', 'unqualified_count')) {
                $table->unsignedInteger('unqualified_count')->default(0)->after('qualified_count');
            }
        });

        Schema::table('masterlist_records', function (Blueprint $table) {
            if (! Schema::hasColumn('masterlist_records', 'eligibility_status')) {
                $table->string('eligibility_status')->default('pending')->index()->after('verification_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('masterlist_records', function (Blueprint $table) {
            if (Schema::hasColumn('masterlist_records', 'eligibility_status')) {
                $table->dropColumn('eligibility_status');
            }
        });

        Schema::table('scholarship_masterlists', function (Blueprint $table) {
            if (Schema::hasColumn('scholarship_masterlists', 'qualified_count')) {
                $table->dropColumn('qualified_count');
            }

            if (Schema::hasColumn('scholarship_masterlists', 'unqualified_count')) {
                $table->dropColumn('unqualified_count');
            }
        });

        Schema::dropIfExists('scholarship_policies');
    }
};
