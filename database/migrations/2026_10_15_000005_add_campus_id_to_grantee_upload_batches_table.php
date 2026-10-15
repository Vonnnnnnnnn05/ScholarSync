<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grantee_upload_batches', function (Blueprint $table) {
            $table->foreignId('campus_id')->nullable()->after('uploaded_by')->constrained('campuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('grantee_upload_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campus_id');
        });
    }
};
