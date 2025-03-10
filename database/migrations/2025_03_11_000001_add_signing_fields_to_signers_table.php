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
        Schema::table('signers', function (Blueprint $table) {
            if (!Schema::hasColumn('signers', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('last_reminded_at');
            }
            
            if (!Schema::hasColumn('signers', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('viewed_at');
            }
            
            if (!Schema::hasColumn('signers', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('signed_at');
            }
            
            if (!Schema::hasColumn('signers', 'decline_reason')) {
                $table->text('decline_reason')->nullable()->after('declined_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signers', function (Blueprint $table) {
            $table->dropColumn([
                'viewed_at',
                'signed_at',
                'declined_at',
                'decline_reason',
            ]);
        });
    }
};
