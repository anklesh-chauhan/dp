<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum SupplierCategory: string
{
    case RawMaterial = 'raw_material';
    case PackagingMaterial = 'packaging_material';
    case ContractManufacturer = 'contract_manufacturer';
    case Laboratory = 'laboratory';
    case Logistics = 'logistics';
    case ServiceProvider = 'service_provider';
    case SoftwareTechnology = 'software_technology';
    case Other = 'other';
}
