<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campuses', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('campus_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        Schema::table('agencies', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });

        DB::table('agencies')->whereNotNull('user_id')->update(['user_id' => null]);
        DB::table('users')->where('role', 'scholarship_agency')->delete();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('campus_id');
        });
        Schema::dropIfExists('campuses');
    }
};
