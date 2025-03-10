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
            $table->timestamp('invited_at')->nullable()->after('declined_at');
            $table->timestamp('last_reminded_at')->nullable()->after('invited_at');
            
            // Ensure we have the correct field name for order
            if (Schema::hasColumn('signers', 'order')) {
                $table->renameColumn('order', 'order_index');
            } else if (!Schema::hasColumn('signers', 'order_index')) {
                $table->integer('order_index')->default(1)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signers', function (Blueprint $table) {
            $table->dropColumn('invited_at');
            $table->dropColumn('last_reminded_at');
            
            if (Schema::hasColumn('signers', 'order_index')) {
                $table->renameColumn('order_index', 'order');
            }
        });
    }
};
