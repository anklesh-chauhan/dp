<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsLookupModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariableDataType extends Model
{
    use IsLookupModel;

    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    public const NUMBER = 'number';

    public const DATE = 'date';

    public const SELECT = 'select';

    public const BOOLEAN = 'boolean';

    public const USER = 'user';

    public const DEPARTMENT = 'department';

    public const SOP_REFERENCE = 'sop_reference';

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return HasMany<SopTemplateVariable, $this>
     */
    public function templateVariables(): HasMany
    {
        return $this->hasMany(SopTemplateVariable::class);
    }
}
