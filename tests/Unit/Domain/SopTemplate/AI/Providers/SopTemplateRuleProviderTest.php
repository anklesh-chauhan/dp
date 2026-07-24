<?php

declare(strict_types=1);

use App\Domain\SopTemplate\AI\Providers\SopTemplateRuleProvider;
use App\Domain\SopTemplate\AI\Rules\EveryPlaceholderMustBeDefinedRule;
use App\Domain\SopTemplate\AI\Rules\EveryVariableMustBeReferencedRule;
use App\Domain\SopTemplate\AI\Rules\SnakeCaseVariableNamesRule;
use App\Domain\SopTemplate\AI\Rules\UniqueVariableNamesRule;
use App\Domain\SopTemplate\AI\Rules\ValidSectionStructureRule;
use App\Domain\SopTemplate\AI\Rules\ValidVariableStructureRule;
use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysisResolver;

describe('SopTemplateRuleProvider', function (): void {

    it('returns all integrity rules', function (): void {
        $provider = new SopTemplateRuleProvider(
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
