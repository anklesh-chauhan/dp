<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\AI\Providers;

use App\Domain\DocumentTemplate\AI\Rules\EveryPlaceholderMustBeDefinedRule;
use App\Domain\DocumentTemplate\AI\Rules\EveryVariableMustBeReferencedRule;
use App\Domain\DocumentTemplate\AI\Rules\SnakeCaseVariableNamesRule;
use App\Domain\DocumentTemplate\AI\Rules\UniqueVariableNamesRule;
use App\Domain\DocumentTemplate\AI\Rules\ValidSectionStructureRule;
use App\Domain\DocumentTemplate\AI\Rules\ValidVariableStructureRule;
use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;

final readonly class DocumentTemplateRuleProvider
{
    public function __construct(
        private GeneratedTemplateAnalysisResolver $analysisResolver,
    ) {}

    public function rules(): ValidationRuleCollection
    {
        return new ValidationRuleCollection([
            new ValidSectionStructureRule,
            new ValidVariableStructureRule,
            new SnakeCaseVariableNamesRule($this->analysisResolver),
            new UniqueVariableNamesRule($this->analysisResolver),
            new EveryPlaceholderMustBeDefinedRule($this->analysisResolver),
            new EveryVariableMustBeReferencedRule($this->analysisResolver),
        ]);
    }
}
