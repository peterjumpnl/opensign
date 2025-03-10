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
        if (!Schema::hasTable('signatures')) {
            Schema::create('signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('signer_id')->constrained()->onDelete('cascade');
                $table->foreignId('field_id')->constrained('signature_fields')->onDelete('cascade');
                $table->longText('value');
                $table->string('field_type');
                $table->timestamp('signed_at')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                
                // Ensure a signer can only sign a field once
                $table->unique(['signer_id', 'field_id']);
            });
        } else {
            // If the table exists, update it with any missing columns
            Schema::table('signatures', function (Blueprint $table) {
                if (!Schema::hasColumn('signatures', 'field_id')) {
                    $table->foreignId('field_id')->nullable()->constrained('signature_fields')->onDelete('cascade');
                }
                
                if (!Schema::hasColumn('signatures', 'value')) {
                    $table->longText('value')->nullable();
                }
                
                if (!Schema::hasColumn('signatures', 'field_type')) {
                    $table->string('field_type')->nullable();
                }
                
                if (!Schema::hasColumn('signatures', 'signed_at')) {
                    $table->timestamp('signed_at')->nullable();
                }
                
                if (!Schema::hasColumn('signatures', 'ip_address')) {
                    $table->string('ip_address')->nullable();
                }
                
                if (!Schema::hasColumn('signatures', 'user_agent')) {
                    $table->string('user_agent')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
