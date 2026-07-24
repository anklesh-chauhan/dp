<?php

namespace App\Domain\DMS\Contracts;

interface ControlledDocument
{
    public function controlledDocumentReference(): string;

    public function controlledDocumentTitle(): string;

    public function controlledDocumentVersion(): int;
}
