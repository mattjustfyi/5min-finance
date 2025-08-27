<?php

namespace App\Models;

use App\Enums\RetirementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function rules(): array
    {
        return [
            'name' => 'required',
            'currency' => 'required|size:3|regex:/^[A-Z]{3}$/',
            'conversion_rate' => 'required|numeric|min:0',
            'retirement_type' => 'required|in:retirement,non_retirement',
        ];
    }
}
