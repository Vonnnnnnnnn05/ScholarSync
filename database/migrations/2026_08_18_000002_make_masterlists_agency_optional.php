<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_masterlists', function (Blueprint $table): void {
            $table->unsignedBigInteger('agency_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_masterlists', function (Blueprint $table): void {
            $table->unsignedBigInteger('agency_id')->nullable(false)->change();
        });
    }
};
