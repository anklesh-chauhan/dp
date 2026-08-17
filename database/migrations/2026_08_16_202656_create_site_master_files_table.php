<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_master_files', function (Blueprint $table) {
            $table->id();
            $table->string('smf_number')->unique();
            $table->string('title');
            $table->string('status')->default(SiteMasterFileStatus::Draft->value)->index();
            $table->unsignedInteger('version')->default(1);
            $table->json('sections')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_master_files');
    }
};
