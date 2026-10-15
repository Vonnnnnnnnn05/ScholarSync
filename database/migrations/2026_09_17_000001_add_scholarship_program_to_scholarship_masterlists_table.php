<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_masterlists', function (Blueprint $table): void {
            if (! Schema::hasColumn('scholarship_masterlists', 'scholarship_program')) {
                $table->string('scholarship_program')->nullable()->index()->after('file_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_masterlists', function (Blueprint $table): void {
            if (Schema::hasColumn('scholarship_masterlists', 'scholarship_program')) {
                $table->dropColumn('scholarship_program');
            }
        });
    }
};
