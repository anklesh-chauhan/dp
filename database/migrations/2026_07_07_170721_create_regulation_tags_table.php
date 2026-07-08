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
        Schema::create('regulation_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('description')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('regulation_tag_document_type', function (Blueprint $table) {
            $table->foreignId('regulation_tag_id')->constrained('regulation_tags');
            $table->foreignId('document_type_id')->constrained('document_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regulation_tags');
        Schema::dropIfExists('regulation_tag_document_type');
    }
};
