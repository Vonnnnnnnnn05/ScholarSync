<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('scholarship_requirements');
        Schema::dropIfExists('scholarship_applications');
        Storage::disk('local')->deleteDirectory('scholarship-renewals');
    }

    public function down(): void
    {
        // Removed feature data is intentionally not recreated.
    }
};
