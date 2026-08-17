<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessment_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('risk_assessment_id')
                ->constrained('risk_assessments')
                ->cascadeOnDelete();
            $table->morphs('linkable');
            $table->string('link_role')->nullable();
            $table->timestamps();

            $table->unique(
                ['risk_assessment_id', 'linkable_type', 'linkable_id'],
                'risk_assessment_links_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessment_links');
    }
};
