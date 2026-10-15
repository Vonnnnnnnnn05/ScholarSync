<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grantee_upload_batches', function (Blueprint $table) {
            $table->string('status', 50)->default('staged')->change();
            $table->timestamp('sent_to_registrar_at')->nullable()->after('coordinator_notes');
            $table->foreignId('sent_to_registrar_by')->nullable()->after('sent_to_registrar_at')->constrained('users')->nullOnDelete();
            $table->text('coordinator_to_registrar_notes')->nullable()->after('sent_to_registrar_by');
            $table->timestamp('registrar_verified_at')->nullable()->after('coordinator_to_registrar_notes');
            $table->foreignId('registrar_verified_by')->nullable()->after('registrar_verified_at')->constrained('users')->nullOnDelete();
            $table->text('registrar_notes')->nullable()->after('registrar_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('grantee_upload_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registrar_verified_by');
            $table->dropConstrainedForeignId('sent_to_registrar_by');
            $table->dropColumn([
                'sent_to_registrar_at',
                'coordinator_to_registrar_notes',
                'registrar_verified_at',
                'registrar_notes',
            ]);
        });
    }
};
