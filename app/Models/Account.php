<?php

namespace App\Models;

use App\Enums\RetirementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
