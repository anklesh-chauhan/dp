<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class ExecutionGrid extends Field
{
    protected string $view = 'filament.forms.components.execution-grid';

    /** @var array<int, array<string, mixed>> | Closure */
    protected array|Closure $executionColumns = [];

    /** @var array<int|string, string> | Closure */
    protected array|Closure $verifiers = [];

    protected bool|Closure $scheduled = false;

    /** @param array<int, array<string, mixed>> | Closure $columns */
    public function executionColumns(array|Closure $columns): static
    {
        $this->executionColumns = $columns;

        return $this;
    }

    /** @return array<int, array<string, mixed>> */
    public function getExecutionColumns(): array
    {
        return $this->evaluate($this->executionColumns);
    }

    /** @param array<int|string, string> | Closure $verifiers */
    public function verifiers(array|Closure $verifiers): static
    {
        $this->verifiers = $verifiers;

        return $this;
    }

    /** @return array<int|string, string> */
    public function getVerifiers(): array
    {
        return $this->evaluate($this->verifiers);
    }

    public function scheduled(bool|Closure $condition = true): static
    {
        $this->scheduled = $condition;

        return $this;
    }

    public function isScheduled(): bool
    {
        return (bool) $this->evaluate($this->scheduled);
    }
}
