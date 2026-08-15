<?php

declare(strict_types=1);

namespace App\Support\AppGuide;

use App\Enums\ProductModule;
use App\Filament\Resources\KnowledgeGuides\KnowledgeGuideResource;
use App\Support\Modules\ModuleManager;

class AppGuide
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
    ) {}

    /**
     * @return list<array{id: string, title: string, description: string, groupLabel: string|null, itemLabel: string|null}>
     */
    public function steps(): array
    {
        $steps = [
            [
                'id' => 'welcome',
                'title' => 'Welcome to QualiGxP',
                'description' => 'This short tour shows where the main menus live and what each area is for. You can restart it anytime from your user menu.',
                'groupLabel' => null,
                'itemLabel' => null,
            ],
            [
                'id' => 'dms',
                'title' => 'Document Management (DMS)',
                'description' => 'Controlled documents, templates, issuance, and GMP execution records live under DMS.',
                'groupLabel' => 'DMS',
                'itemLabel' => null,
            ],
            [
                'id' => 'approvals',
                'title' => 'Approvals & queues',
                'description' => 'Review and sign off pending work from My Approval Queue and related approval menus under DMS.',
                'groupLabel' => 'DMS',
                'itemLabel' => 'My Approval Queue',
            ],
            [
                'id' => 'help',
                'title' => 'Help & Knowledge',
                'description' => 'Open the Knowledge Library for deeper how-to guides on document control and workflows.',
                'groupLabel' => 'DMS · Help & Knowledge',
                'itemLabel' => null,
            ],
            [
                'id' => 'settings',
                'title' => 'DMS Settings',
                'description' => 'Configure numbering, templates, issuance, workflows, and system security here.',
                'groupLabel' => 'DMS · Settings',
                'itemLabel' => null,
            ],
            [
                'id' => 'identity',
                'title' => 'Identity & Access',
                'description' => 'Manage users, organizations, departments, designations, and roles under Core.',
                'groupLabel' => 'Core · Identity & Access',
                'itemLabel' => null,
            ],
        ];

        if ($this->moduleManager->enabled(ProductModule::QMS)) {
            $steps[] = [
                'id' => 'qms',
                'title' => 'Quality Management (QMS)',
                'description' => 'Deviations, investigations, CAPA, change control, and related quality records live under QMS.',
                'groupLabel' => 'QMS',
                'itemLabel' => null,
            ];
        }

        if ($this->moduleManager->enabled(ProductModule::AI)) {
            $steps[] = [
                'id' => 'ai',
                'title' => 'AI Management',
                'description' => 'Use AI-assisted drafting and review AI executions from this menu when the AI module is enabled.',
                'groupLabel' => 'AI Management',
                'itemLabel' => null,
            ];
        }

        $steps[] = [
            'id' => 'finish',
            'title' => 'You are ready',
            'description' => 'Continue in the Knowledge Library for detailed guides, or explore the menus at your own pace.',
            'groupLabel' => 'DMS · Help & Knowledge',
            'itemLabel' => 'Knowledge Library',
        ];

        return $steps;
    }

    /**
     * @return array{
     *     steps: list<array{id: string, title: string, description: string, groupLabel: string|null, itemLabel: string|null}>,
     *     knowledgeLibraryUrl: string,
     *     completeUrl: string,
     *     restartUrl: string
     * }
     */
    public function payload(): array
    {
        return [
            'steps' => $this->steps(),
            'knowledgeLibraryUrl' => KnowledgeGuideResource::getUrl('index'),
            'completeUrl' => route('app-guide.complete'),
            'restartUrl' => route('app-guide.restart'),
        ];
    }

    /**
     * @return list<string>
     */
    public function stepIds(): array
    {
        return array_column($this->steps(), 'id');
    }
}
