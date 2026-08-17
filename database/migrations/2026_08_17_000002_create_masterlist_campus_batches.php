<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masterlist_campus_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('masterlist_id')->constrained('scholarship_masterlists')->cascadeOnDelete();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('with_coordinator')->index();
            $table->foreignId('submitted_to_registrar_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_to_registrar_at')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('submitted_to_chairman_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_to_chairman_at')->nullable();
            $table->timestamps();
            $table->unique(['masterlist_id', 'campus_id']);
        });

        Schema::table('masterlist_records', function (Blueprint $table): void {
            $table->foreignId('verified_by')->nullable()->after('verification_status')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('masterlist_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verified_at');
        });
        Schema::dropIfExists('masterlist_campus_batches');
    }
};
