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
        Schema::create('signature_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('signer_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('field_id')->comment('Frontend field identifier');
            $table->string('type')->comment('signature, initial, date, checkbox');
            $table->integer('page')->default(1);
            $table->float('x_position');
            $table->float('y_position');
            $table->float('width');
            $table->float('height');
            $table->boolean('is_signed')->default(false);
            $table->json('signature_data')->nullable()->comment('Signature image data or value');
            $table->timestamps();
            
            // Ensure field_id is unique per document
            $table->unique(['document_id', 'field_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_fields');
    }
};
