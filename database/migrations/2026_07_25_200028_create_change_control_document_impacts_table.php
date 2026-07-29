<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DocumentImpactAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_control_document_impacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_control_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('source_document_id')
                ->nullable()
                ->constrained('controlled_documents')
                ->restrictOnDelete();
            $table->foreignId('result_document_id')
                ->nullable()
                ->constrained('controlled_documents')
                ->restrictOnDelete();
            $table->string('required_action')
                ->default(DocumentImpactAction::Revise->value)
                ->index();
            $table->text('rationale');
            $table->timestamps();

            $table->unique(
                ['change_control_id', 'source_document_id', 'required_action'],
                'cc_document_impact_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_control_document_impacts');
    }
};
