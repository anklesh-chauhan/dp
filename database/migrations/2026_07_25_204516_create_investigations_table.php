<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\InvestigationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigations', function (Blueprint $table) {
            $table->id();
            $table->string('investigation_number')->unique();
            $table->foreignId('deviation_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('status')->default(InvestigationStatus::Draft->value)->index();
            $table->foreignId('lead_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('methodology');
            $table->text('root_cause')->nullable();
            $table->text('conclusion')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->date('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigations');
    }
};
