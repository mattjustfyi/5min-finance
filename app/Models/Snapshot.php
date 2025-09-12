<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

class Snapshot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'date',
        'notes',
        'accounts',
        'goals',
        'debts',
    ];

    protected $casts = [
        'date' => 'date',
        'accounts' => 'array',
        'goals' => 'array',
        'debts' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::saving(function (Snapshot $snapshot) {
            $snapshot->validateUniqueEntries();
        });
    }


    // Ensure that only a date is saved in the database. Even though the migration uses date(), SQLite doesn't
    // enforce this. It's essentially a text column AFAIK.
    protected function date(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                // Allow Carbon instances (from now(), etc.) to pass through
                if ($value instanceof Carbon) {
                    return $value->toDateString();
                }

                // Validate string dates before parsing
                try {
                    $carbonDate = Carbon::createFromFormat('Y-m-d', $value);
                    if ($carbonDate->format('Y-m-d') !== $value) {
                        throw new InvalidArgumentException('Invalid date format. Date must be a valid date in Y-m-d format.');
                    }
                } catch (\Exception $e) {
                    throw new InvalidArgumentException('Invalid date format. Date must be a valid date in Y-m-d format.');
                }

                return Carbon::parse($value)->toDateString();
            }
        );
    }


    private function validateUniqueEntries(): void

    {
        // Validate unique account IDs
        if (is_array($this->accounts) && !empty($this->accounts)) {
            $accountIds = array_keys($this->accounts);
            if (count($accountIds) !== count(array_unique($accountIds))) {
                throw new InvalidArgumentException('Duplicate accounts are not allowed in a snapshot.');
            }
        }

        // Validate unique goal IDs
        if (is_array($this->goals) && !empty($this->goals)) {
            $goalIds = collect($this->goals)->pluck('goalId')->filter()->toArray();
            if (count($goalIds) !== count(array_unique($goalIds))) {
                throw new InvalidArgumentException('Duplicate goals are not allowed in a snapshot.');
            }
        }

        // Validate unique debt IDs
        if (is_array($this->debts) && !empty($this->debts)) {
            $debtIds = collect($this->debts)->pluck('debtId')->filter()->toArray();
            if (count($debtIds) !== count(array_unique($debtIds))) {
                throw new InvalidArgumentException('Duplicate debts are not allowed in a snapshot.');
            }
        }
    }
}
