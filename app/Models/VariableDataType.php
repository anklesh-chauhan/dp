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

    public const LONG_TEXT = 'long_text';

    /** @deprecated Use {@see self::LONG_TEXT} instead. */
    public const TEXTAREA = 'textarea';

    public const RICH_TEXT = 'rich_text';

    public const INTEGER = 'integer';

    public const DECIMAL = 'decimal';

    /** @deprecated Use {@see self::DECIMAL} instead. */
    public const NUMBER = 'number';

    public const CURRENCY = 'currency';

    public const PERCENTAGE = 'percentage';

    public const DATE = 'date';

    public const DATETIME = 'datetime';

    public const TIME = 'time';

    public const BOOLEAN = 'boolean';

    public const CHECKBOX = 'checkbox';

    public const SELECT = 'select';

    public const MULTI_SELECT = 'multi_select';

    public const RADIO = 'radio';

    public const USER = 'user';

    public const EMPLOYEE = 'employee';

    public const DEPARTMENT = 'department';

    public const DESIGNATION = 'designation';

    public const SOP_REFERENCE = 'sop_reference';

    public const CONTROLLED_DOCUMENT = 'controlled_document';

    public const DOCUMENT_NUMBER = 'document_number';

    public const FILE = 'file';

    public const IMAGE = 'image';

    public const URL = 'url';

    public const EMAIL = 'email';

    public const PHONE = 'phone';

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return HasMany<DocumentTemplateVariable, $this>
     */
    public function templateVariables(): HasMany
    {
        return $this->hasMany(DocumentTemplateVariable::class);
    }
}
