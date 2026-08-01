<?php

declare(strict_types=1);

use App\Models\FormDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores one draft per user and form', function (): void {
    $user = User::factory()->create();

    $draft = FormDraft::query()->updateOrCreate(
        ['user_id' => $user->id, 'form_key' => 'qms.deviations.create'],
        ['payload' => ['title' => 'Initial deviation'], 'last_saved_at' => now()],
    );

    FormDraft::query()->updateOrCreate(
        ['user_id' => $user->id, 'form_key' => 'qms.deviations.create'],
        ['payload' => ['title' => 'Updated deviation'], 'last_saved_at' => now()],
    );

    expect(FormDraft::query()->whereKey($draft->id)->count())->toBe(1)
        ->and(FormDraft::query()->whereKey($draft->id)->value('payload'))
        ->toBe(['title' => 'Updated deviation']);
});
