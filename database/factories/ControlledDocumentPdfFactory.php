<?php

namespace Database\Factories;

use App\Models\ControlledDocument;
use App\Models\ControlledDocumentPdf;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentPdf>
 */
class ControlledDocumentPdfFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'controlled_document_id' => ControlledDocument::factory(),
            'report_template_id' => ReportTemplate::factory(),
            'artifact_key' => hash('sha256', fake()->unique()->uuid()),
            'document_version' => 1,
            'template_layout_key' => fake()->slug(3),
            'disk' => 'local',
            'path' => 'controlled-document-pdfs/'.fake()->uuid().'.pdf',
            'filename' => fake()->slug(3).'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 32,
            'sha256' => hash('sha256', fake()->uuid()),
            'renderer' => 'gotenberg',
            'renderer_version' => '8.34.0',
            'generated_by' => User::factory(),
            'generated_at' => now(),
            'metadata' => [],
        ];
    }
}
