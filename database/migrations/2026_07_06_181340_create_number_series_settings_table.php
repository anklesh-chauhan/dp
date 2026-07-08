<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_series_settings', function (Blueprint $table) {
            $table->id();
            $table->string('default_prefix_pattern')->default('{type}-{department}-');
            $table->unsignedTinyInteger('default_padding_length')->default(5);
            $table->string('default_suffix')->default('');
            $table->string('overflow_behavior')->default('expand');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_series_settings');
    }
};
