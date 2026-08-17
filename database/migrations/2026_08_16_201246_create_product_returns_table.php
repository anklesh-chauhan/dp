<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductReturnDisposition;
use App\Domain\QMS\Enums\ProductReturnStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->string('status')->default(ProductReturnStatus::Draft->value)->index();
            $table->string('disposition')->default(ProductReturnDisposition::Pending->value)->index();
            $table->string('product_name')->index();
            $table->string('batch_number')->nullable()->index();
            $table->decimal('quantity', 12, 3)->nullable();
            $table->string('unit')->nullable();
            $table->text('reason');
            $table->string('source')->nullable();
            $table->foreignId('product_recall_id')->nullable()->constrained('product_recalls')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('quarantined_at')->nullable();
            $table->timestamp('dispositioned_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('qa_disposition_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_returns');
    }
};
