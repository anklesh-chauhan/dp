<?php

declare(strict_types=1);

namespace App\Domain\SopTemplate\AI\Providers;

use App\Domain\SopTemplate\AI\Rules\EveryPlaceholderMustBeDefinedRule;
use App\Domain\SopTemplate\AI\Rules\EveryVariableMustBeReferencedRule;
use App\Domain\SopTemplate\AI\Rules\SnakeCaseVariableNamesRule;
use App\Domain\SopTemplate\AI\Rules\UniqueVariableNamesRule;
use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;

final readonly class SopTemplateRuleProvider
{
    public function __construct(
        private GeneratedTemplateAnalysisResolver $analysisResolver,
    ) {
    }

    public function rules(): ValidationRuleCollection
    {
        return new ValidationRuleCollection([
            new SnakeCaseVariableNamesRule($this->analysisResolver),
            new UniqueVariableNamesRule($this->analysisResolver),
            new EveryPlaceholderMustBeDefinedRule($this->analysisResolver),
            new EveryVariableMustBeReferencedRule($this->analysisResolver),
        ]);
    }
}
