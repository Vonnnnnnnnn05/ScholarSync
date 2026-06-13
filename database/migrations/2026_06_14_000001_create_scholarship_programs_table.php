<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('fund_source')->index();
            $table->string('agency_name')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['name', 'fund_source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_programs');
    }
};
