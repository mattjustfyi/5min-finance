<?php

namespace App\Models;

use App\Enums\RetirementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'currency',
        'conversion_rate',
        'retirement_type',
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:4',
        'retirement_type' => RetirementType::class,
    ];

    protected $attributes = [
        'retirement_type' => RetirementType::NON_RETIREMENT,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getValidationRules(?int $userId = null, ?int $accountId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                Rule::unique('accounts', 'name')
                    ->where('user_id', $userId)
                    ->ignore($accountId)
                    ->whereNull('deleted_at'),
            ],
            'currency' => ['required', 'string', 'size:3'],
            'conversion_rate' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'retirement_type' => ['sometimes', Rule::enum(RetirementType::class)],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

}
