<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Models\Department;
use App\Models\SopDocument;
use Illuminate\Support\Str;

class DocumentNumberGeneratorService
{
    public function generate(Department $department): string
    {
        $prefix = sprintf('SOP-%s-', Str::upper($department->code));
        $latestNumber = SopDocument::query()
            ->where('document_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('document_number')
            ->map(fn (string $documentNumber): int => (int) Str::afterLast($documentNumber, '-'))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($latestNumber + 1), 5, '0', STR_PAD_LEFT);
    }
}
