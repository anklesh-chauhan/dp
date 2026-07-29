<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\AI\Services;

use App\Domain\DocumentTemplate\AI\Providers\DocumentTemplateRuleProvider;
use App\Domain\DocumentTemplate\AI\Rules\ValidSectionStructureRule;
use App\Domain\DocumentTemplate\AI\Rules\ValidVariableStructureRule;
use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysis;
use App\Domain\DocumentTemplate\AI\Support\PlaceholderExtractor;
use App\Foundation\AI\Validation\Contracts\ValidationEngine;
use App\Foundation\AI\Validation\Contracts\ValidationIssue;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

final readonly class DocumentTemplateIntegrityService
{
    public function __construct(
        private ValidationEngine $validationEngine,
        private DocumentTemplateRuleProvider $ruleProvider,
        private PlaceholderExtractor $placeholderExtractor,
    ) {}

    public function validate(
        array $generatedTemplate,
    ): ValidationResult {
        $analysis = GeneratedTemplateAnalysis::analyze(
            $generatedTemplate,
            $this->placeholderExtractor,
        );

        $context = new ValidationContext(
            artifactType: 'document_template',
            attributes: [
                'analysis' => $analysis,
            ],
        );

        return $this->validationEngine->validate(
            artifact: $generatedTemplate,
            context: $context,
            rules: $this->ruleProvider->rules(),
        );
    }

    public function hasStructuralErrors(
        ValidationResult $result,
    ): bool {
        foreach ($result->issues() as $issue) {
            if (in_array($issue->code(), [
                ValidSectionStructureRule::CODE,
                ValidVariableStructureRule::CODE,
            ], true)) {
                return true;
            }
        }

        return false;
    }

    public function failureMessage(
        ValidationResult $result,
    ): string {
        $issues = $result->issues()->all();

        foreach ($issues as $issue) {
            if (in_array($issue->code(), [
                ValidSectionStructureRule::CODE,
                ValidVariableStructureRule::CODE,
            ], true)) {
                return $issue->message();
            }
        }

        $invalidVariable = $this->firstIssueWithCode(
            $issues,
            'snake_case_variable_names',
        );

        if ($invalidVariable !== null) {
            return sprintf(
                'Generated variable [%s] must use snake_case.',
                $invalidVariable->metadata()['variable'],
            );
        }

        $duplicateVariables = $this->metadataValues(
            $issues,
            'unique_variable_names',
            'variable',
        );

        if ($duplicateVariables !== []) {
            return sprintf(
                'Generated template contains duplicate variables: %s.',
                implode(', ', $duplicateVariables),
            );
        }

        $unreferencedVariables = $this->metadataValues(
            $issues,
            'referenced_variables',
            'variable',
        );

        if ($unreferencedVariables !== []) {
            return sprintf(
                'Generated template contains unreferenced variables: %s.',
                implode(', ', $unreferencedVariables),
            );
        }

        $undefinedPlaceholders = $this->metadataValues(
            $issues,
            'defined_placeholders',
            'placeholder',
        );

        if ($undefinedPlaceholders !== []) {
            return sprintf(
                'Generated template contains undefined placeholders: %s.',
                implode(', ', $undefinedPlaceholders),
            );
        }

        if ($issues === []) {
            return 'Generated template validation failed.';
        }

        return $issues[0]->message();
    }

    /**
     * @param  array<int, ValidationIssue>  $issues
     */
    private function firstIssueWithCode(
        array $issues,
        string $code,
    ): ?ValidationIssue {
        foreach ($issues as $issue) {
            if ($issue->code() === $code) {
                return $issue;
            }
        }

        return null;
    }

    /**
     * @param  array<int, ValidationIssue>  $issues
     * @return array<int, string>
     */
    private function metadataValues(
        array $issues,
        string $code,
        string $key,
    ): array {
        $values = [];

        foreach ($issues as $issue) {
            $value = $issue->metadata()[$key] ?? null;

            if ($issue->code() !== $code || ! is_string($value)) {
                continue;
            }

            $values[] = $value;
        }

        return array_values(array_unique($values));
    }
}
