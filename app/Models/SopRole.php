<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsLookupModel;
use Illuminate\Database\Eloquent\Model;

class SopRole extends Model
{
    use IsLookupModel;

    public const ADMINISTRATOR = 'sop administrator';

    public const MAKER = 'sop maker';

    public const CHECKER = 'sop checker';

    public const APPROVER = 'sop approver';

    protected $fillable = ['code', 'name', 'sort_order'];
}
