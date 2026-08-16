<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masterlist_records', fn (Blueprint $table) => $table->foreignId('campus_id')->nullable()->after('masterlist_id')->constrained()->nullOnDelete());
        Schema::table('registrar_students', fn (Blueprint $table) => $table->foreignId('campus_id')->nullable()->after('id')->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('masterlist_records', fn (Blueprint $table) => $table->dropConstrainedForeignId('campus_id'));
        Schema::table('registrar_students', fn (Blueprint $table) => $table->dropConstrainedForeignId('campus_id'));
    }
};
