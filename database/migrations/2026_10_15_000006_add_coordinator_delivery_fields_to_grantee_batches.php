<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grantee_upload_batches', function (Blueprint $table) {
            $table->timestamp('delivered_to_coordinator_at')->nullable()->after('committed_by');
            $table->foreignId('delivered_to_coordinator_by')->nullable()->after('delivered_to_coordinator_at')->constrained('users')->nullOnDelete();
            $table->timestamp('coordinator_endorsed_at')->nullable()->after('delivered_to_coordinator_by');
            $table->foreignId('coordinator_endorsed_by')->nullable()->after('coordinator_endorsed_at')->constrained('users')->nullOnDelete();
            $table->text('coordinator_notes')->nullable()->after('coordinator_endorsed_by');
        });
    }

    public function down(): void
    {
        Schema::table('grantee_upload_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coordinator_endorsed_by');
            $table->dropConstrainedForeignId('delivered_to_coordinator_by');
            $table->dropColumn([
                'delivered_to_coordinator_at',
                'coordinator_endorsed_at',
                'coordinator_notes',
            ]);
        });
    }
};
