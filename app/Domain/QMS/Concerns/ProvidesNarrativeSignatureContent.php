<?php

declare(strict_types=1);

namespace App\Domain\QMS\Concerns;

trait ProvidesNarrativeSignatureContent
{
    /**
     * @return array<string, mixed>
     */
    public function electronicSignatureContentPayload(): array
    {
        $payload = [
            'model' => $this->getMorphClass(),
            'id' => $this->getKey(),
        ];

        foreach ($this->getAttributes() as $attribute => $value) {
            if ($this->excludesFromElectronicSignatureContent($attribute)) {
                continue;
            }

            $payload[$attribute] = $value;
        }

        return $payload;
    }

    private function excludesFromElectronicSignatureContent(string $attribute): bool
    {
        if (in_array($attribute, ['id', 'status', 'disposition', 'created_at', 'updated_at'], true)) {
            return true;
        }

        return str_ends_with($attribute, '_at');
    }
}
