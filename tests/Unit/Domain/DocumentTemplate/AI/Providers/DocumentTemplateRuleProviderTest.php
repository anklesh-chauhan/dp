<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\AI\Providers\DocumentTemplateRuleProvider;
use App\Domain\DocumentTemplate\AI\Rules\EveryPlaceholderMustBeDefinedRule;
use App\Domain\DocumentTemplate\AI\Rules\EveryVariableMustBeReferencedRule;
use App\Domain\DocumentTemplate\AI\Rules\SnakeCaseVariableNamesRule;
use App\Domain\DocumentTemplate\AI\Rules\UniqueVariableNamesRule;
use App\Domain\DocumentTemplate\AI\Rules\ValidSectionStructureRule;
use App\Domain\DocumentTemplate\AI\Rules\ValidVariableStructureRule;
use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysisResolver;

describe('DocumentTemplateRuleProvider', function (): void {

    it('returns all integrity rules', function (): void {
        $provider = new DocumentTemplateRuleProvider(
            new GeneratedTemplateAnalysisResolver,
        );

        $rules = $provider->rules();

        expect($rules)
            ->toHaveCount(6);

        expect($rules->all())
            ->sequence(
                fn ($rule) => $rule->toBeInstanceOf(ValidSectionStructureRule::class),
                fn ($rule) => $rule->toBeInstanceOf(ValidVariableStructureRule::class),
                fn ($rule) => $rule->toBeInstanceOf(SnakeCaseVariableNamesRule::class),
                fn ($rule) => $rule->toBeInstanceOf(UniqueVariableNamesRule::class),
                fn ($rule) => $rule->toBeInstanceOf(EveryPlaceholderMustBeDefinedRule::class),
                fn ($rule) => $rule->toBeInstanceOf(EveryVariableMustBeReferencedRule::class),
            );
    });
});
