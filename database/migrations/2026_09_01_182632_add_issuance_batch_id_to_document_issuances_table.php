<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_issuance_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('controlled_documents')->restrictOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('copy_count');
            $table->string('first_issuance_number')->nullable();
            $table->string('last_issuance_number')->nullable();
            $table->string('pack_status', 20)->default('pending')->index();
            $table->string('pack_disk')->nullable();
            $table->string('pack_path')->nullable();
            $table->string('pack_filename')->nullable();
            $table->string('pack_sha256', 64)->nullable();
            $table->unsignedBigInteger('pack_size_bytes')->nullable();
            $table->text('pack_error')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::table('document_issuances', function (Blueprint $table): void {
            $table->foreignId('issuance_batch_id')
                ->nullable()
                ->after('document_id')
                ->constrained('document_issuance_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_issuances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('issuance_batch_id');
        });

        Schema::dropIfExists('document_issuance_batches');
    }
};
