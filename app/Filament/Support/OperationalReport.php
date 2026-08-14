<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\ProductModule;
use Filament\Pages\Page;

final readonly class OperationalReport
{
    /**
     * @param  class-string<Page>  $page
     */
    public function __construct(
        public string $key,
        public ProductModule $module,
        public string $title,
        public string $description,
        public string $permission,
        public string $page,
    ) {}

    public function url(): string
    {
        return $this->page::getUrl();
    }

    public function navigationGroup(): string
    {
        return match ($this->module) {
            ProductModule::DMS => 'DMS · Reports',
            ProductModule::QMS => 'QMS · Reports',
            ProductModule::AI => 'AI Management',
        };
    }
}
