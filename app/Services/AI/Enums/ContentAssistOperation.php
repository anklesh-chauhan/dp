<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum ContentAssistOperation: string
{
    case Create = 'create';

    case Polish = 'polish';

    case Shorten = 'shorten';

    public function instruction(ContentAssistFormat $format): string
    {
        return match ($this) {
            self::Create => match ($format) {
                ContentAssistFormat::Html => 'Create complete reusable pharmaceutical document content for this field.',
                ContentAssistFormat::Plain => 'Draft clear pharmaceutical document text for this field.',
            },
            self::Polish => 'Polish and formalize the text using clear, professional pharmaceutical QMS language while preserving meaning.',
            self::Shorten => 'Shorten the text while preserving all important meaning, requirements, and controls.',
        };
    }
}
