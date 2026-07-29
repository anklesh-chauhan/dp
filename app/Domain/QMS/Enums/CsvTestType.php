<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum CsvTestType: string
{
    case InstallationQualification = 'iq';
    case OperationalQualification = 'oq';
    case PerformanceQualification = 'pq';
    case UserAcceptance = 'uat';
    case Security = 'security';
    case DataMigration = 'data_migration';
    case BackupRestore = 'backup_restore';
    case DisasterRecovery = 'disaster_recovery';
}
