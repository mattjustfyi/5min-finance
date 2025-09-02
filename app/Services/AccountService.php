<?php

namespace App\Services;

use App\Models\Account;
use App\Enums\RetirementType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function createAccount(array $data, int $userId): Account
    {
        // Ensure user_id is set and retirement_type defaults to non_retirement
        $data['user_id'] = $userId;
        $data['retirement_type'] = $data['retirement_type'] ?? RetirementType::NON_RETIREMENT;

        // Validate the data
        $validator = Validator::make($data, Account::getValidationRules($userId));

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Create and return the account
        return Account::create($validator->validated());
    }

    public function canCreateAccount(string $name, int $userId): bool
    {
        return !Account::where('user_id', $userId)
            ->where('name', $name)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function validateAccountData(array $data, int $userId): array
    {
        $data['user_id'] = $userId;
        $data['retirement_type'] = $data['retirement_type'] ?? RetirementType::NON_RETIREMENT;

        $validator = Validator::make($data, Account::getValidationRules($userId));

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}