<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum LLMCapability: string
{
    case TEXT_GENERATION = 'text_generation';

    case STRUCTURED_OUTPUT = 'structured_output';

    case VISION = 'vision';

    case EMBEDDINGS = 'embeddings';

    case TOOL_CALLING = 'tool_calling';
}
