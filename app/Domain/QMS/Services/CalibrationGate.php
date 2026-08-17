<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\EquipmentAssetCriticality;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Domain\Shared\Contracts\CalibrationReadinessGate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CalibrationGate implements CalibrationReadinessGate
{
    /**
     * Fail open when no critical equipment assets exist, or when the gate is disabled in config.
     *
     * @throws ValidationException
     */
    public function assertNoOverdueCriticalCalibrations(User $actor): void
    {
        if (! (bool) config('modules.calibration_gate', true)) {
            return;
        }

        $hasCriticalAssets = EquipmentAsset::query()
            ->where('criticality', EquipmentAssetCriticality::Critical->value)
            ->exists();

        if (! $hasCriticalAssets) {
            return;
        }

        $overdueExists = EquipmentCalibration::query()
            ->whereIn('status', [
                EquipmentCalibrationStatus::Scheduled->value,
                EquipmentCalibrationStatus::InProgress->value,
            ])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereHas('equipmentAsset', function ($query): void {
                $query->where('criticality', EquipmentAssetCriticality::Critical->value);
            })
            ->exists();

        if ($overdueExists) {
            throw ValidationException::withMessages([
                'calibration' => 'QA approval is blocked while critical equipment has overdue calibrations.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function assertOutOfToleranceHasDeviation(?int $deviationId): void
    {
        if ($deviationId === null) {
            throw ValidationException::withMessages([
                'deviation_id' => 'Out-of-tolerance calibrations must be linked to a deviation before close.',
            ]);
        }
    }
}
