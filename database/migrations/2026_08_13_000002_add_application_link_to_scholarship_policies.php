<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_policies', function (Blueprint $table): void {
            $table->string('application_link', 2048)->nullable()->after('deadline');
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_policies', fn (Blueprint $table) => $table->dropColumn('application_link'));
    }
};
