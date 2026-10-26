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
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('campus_id');
            $table->timestamp('signature_updated_at')->nullable()->after('signature_path');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'signature_updated_at']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['signature_path']);
        });
    }
};
