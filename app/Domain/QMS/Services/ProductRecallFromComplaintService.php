<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\ProductRecallType;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\ProductRecall;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProductRecallFromComplaintService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(
        Complaint $complaint,
        User $actor,
        string $reason,
        ProductRecallType $type = ProductRecallType::Market,
    ): ProductRecall {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Create:ProductRecall') || ! $actor->can('View:Complaint')) {
            throw new AuthorizationException('You do not have permission to open a product recall from this complaint.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required to open a product recall from a complaint.',
            ]);
        }

        return DB::transaction(function () use ($complaint, $actor, $reason, $type): ProductRecall {
            $record = Complaint::query()->lockForUpdate()->findOrFail($complaint->getKey());
            $existing = ProductRecall::query()->where('complaint_id', $record->getKey())->first();

            if ($existing instanceof ProductRecall) {
                return $existing;
            }

            if (in_array($record->status, [ComplaintStatus::Closed, ComplaintStatus::Rejected, ComplaintStatus::Cancelled], true)) {
                throw ValidationException::withMessages([
                    'status' => 'A closed, rejected, or cancelled complaint cannot open a product recall.',
                ]);
            }

            $batchNumbers = filled($record->batch_number) ? [(string) $record->batch_number] : null;

            return ProductRecall::query()->create([
                'type' => $type,
                'status' => ProductRecallStatus::Draft,
                'classification' => ProductRecallClassification::NotClassified,
                'title' => $record->title,
                'description' => trim($reason)."\n\n".$record->description,
                'product_name' => $record->product_name ?: 'Unspecified product',
                'product_code' => null,
                'batch_numbers' => $batchNumbers,
                'market_countries' => $record->market_country_code,
                'complaint_id' => $record->getKey(),
                'owner_id' => $record->owner_id,
                'created_by' => $actor->getKey(),
            ])->refresh();
        });
    }
}
