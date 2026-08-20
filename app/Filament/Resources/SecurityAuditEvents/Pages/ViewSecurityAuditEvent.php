<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityAuditEvents\Pages;

use App\Filament\Resources\SecurityAuditEvents\SecurityAuditEventResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSecurityAuditEvent extends ViewRecord
{
    protected static string $resource = SecurityAuditEventResource::class;
}
