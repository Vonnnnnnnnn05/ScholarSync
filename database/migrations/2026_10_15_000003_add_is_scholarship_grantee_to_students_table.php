<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_scholarship_grantee')->default(false)->after('status');
            $table->index('is_scholarship_grantee');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['is_scholarship_grantee']);
            $table->dropColumn('is_scholarship_grantee');
        });
    }
};
