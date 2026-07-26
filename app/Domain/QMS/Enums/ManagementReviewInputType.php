<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ManagementReviewInputType: string
{
    case PreviousActions = 'previous_actions';
    case AuditResults = 'audit_results';
    case CustomerFeedback = 'customer_feedback';
    case ProcessPerformance = 'process_performance';
    case ProductQuality = 'product_quality';
    case CapaStatus = 'capa_status';
    case SupplierPerformance = 'supplier_performance';
    case RiskManagement = 'risk_management';
    case ResourceAdequacy = 'resource_adequacy';
    case RegulatoryChanges = 'regulatory_changes';
}
