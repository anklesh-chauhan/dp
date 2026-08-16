<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\DMS\Actions\ActivateDueControlledDocumentsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('documents:activate-due')]
#[Description('Activate approved documents whose confirmed effective date has arrived.')]
class ActivateDueControlledDocumentsCommand extends Command
{
    public function handle(ActivateDueControlledDocumentsAction $activateDueControlledDocuments): int
    {
        $activated = $activateDueControlledDocuments->execute();

        $this->info("Activated {$activated} controlled document(s).");

        return self::SUCCESS;
    }
}
