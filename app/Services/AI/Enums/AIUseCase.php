<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum AIUseCase: string
{
    case DOCUMENT_CLASSIFICATION = 'document_classification';

    case DOCUMENT_TYPE_SELECTION = 'document_type_selection';

    case REGULATION_TAGGING = 'regulation_tagging';

    case SOP_GENERATION = 'sop_generation';

    case SOP_REVIEW = 'sop_review';

    case DOCUMENT_SUMMARIZATION = 'document_summarization';

    case CAPA_ROOT_CAUSE_ANALYSIS = 'capa_root_cause_analysis';

    case DEVIATION_ANALYSIS = 'deviation_analysis';

    case CHANGE_CONTROL_IMPACT_ANALYSIS = 'change_control_impact_analysis';

    case TRAINING_QUESTION_GENERATION = 'training_question_generation';

    case DOCUMENT_DESCRIPTION_GENERATION = 'document_description_generation';

    case REGULATED_TEMPLATE_GENERATION = 'regulated_template_generation';
}
