<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->string('prefix_pattern')->nullable();
            $table->unsignedTinyInteger('padding_length')->nullable();
            $table->string('suffix')->nullable();
            $table->timestamps();

            $table->unique('document_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_series');
    }
};
