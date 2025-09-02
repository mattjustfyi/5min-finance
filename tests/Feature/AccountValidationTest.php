<?php

use App\Models\Account;
use App\Models\User;
use App\Enums\RetirementType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('provides correct validation rules', function () {
    $rules = Account::getValidationRules($this->user->id);
    
    expect($rules)->toHaveKeys(['name', 'currency', 'conversion_rate', 'retirement_type', 'user_id']);
    expect($rules['name'])->toContain('required', 'string', 'min:1', 'max:255');
    expect($rules['currency'])->toContain('required', 'string', 'size:3');
    expect($rules['conversion_rate'])->toContain('required', 'numeric', 'min:0.0001', 'max:999999.9999');
});

it('defaults retirement_type to non_retirement', function () {
    $account = new Account();
    
    expect($account->retirement_type)->toBe(RetirementType::NON_RETIREMENT);
});

it('validates unique account names per user', function () {
    // Create first account
    $account1 = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Account'
    ]);
    
    // Try to create second account with same name for same user
    $rules = Account::getValidationRules($this->user->id);
    $validator = validator(['name' => 'Test Account'], $rules);
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('name'))->toContain('already been taken');
});

it('allows same account name for different users', function () {
    $user2 = User::factory()->create();
    
    // Create account for first user
    Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Account'
    ]);
    
    // Create account with same name for second user - should be valid
    $rules = Account::getValidationRules($user2->id);
    $validator = validator([
        'name' => 'Test Account',
        'currency' => 'USD',
        'conversion_rate' => 1.0,
        'user_id' => $user2->id,
    ], $rules);
    
    expect($validator->fails())->toBeFalse();
});

it('ignores current account when checking uniqueness for updates', function () {
    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Account'
    ]);
    
    // Should be able to update the account with the same name
    $rules = Account::getValidationRules($this->user->id, $account->id);
    $validator = validator([
        'name' => 'Test Account',
        'currency' => 'USD',
        'conversion_rate' => 1.0,
        'user_id' => $this->user->id,
    ], $rules);
    
    expect($validator->fails())->toBeFalse();
});

it('validates minimum name length', function () {
    $rules = Account::getValidationRules($this->user->id);
    $validator = validator(['name' => ''], $rules);
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('name'))->toContain('required');
});

it('validates currency format', function () {
    $rules = Account::getValidationRules($this->user->id);
    $baseData = [
        'name' => 'Test Account',
        'conversion_rate' => 1.0,
        'user_id' => $this->user->id,
    ];
    
    // Invalid currency codes
    expect(validator(array_merge($baseData, ['currency' => 'US']), $rules)->fails())->toBeTrue(); // Too short
    expect(validator(array_merge($baseData, ['currency' => 'USDX']), $rules)->fails())->toBeTrue(); // Too long
    
    // Valid currency code
    expect(validator(array_merge($baseData, ['currency' => 'USD']), $rules)->fails())->toBeFalse();
});

it('validates conversion rate bounds', function () {
    $rules = Account::getValidationRules($this->user->id);
    $baseData = [
        'name' => 'Test Account',
        'currency' => 'USD',
        'user_id' => $this->user->id,
    ];
    
    // Invalid rates
    expect(validator(array_merge($baseData, ['conversion_rate' => 0]), $rules)->fails())->toBeTrue(); // Too low
    expect(validator(array_merge($baseData, ['conversion_rate' => -1]), $rules)->fails())->toBeTrue(); // Negative
    expect(validator(array_merge($baseData, ['conversion_rate' => 1000000]), $rules)->fails())->toBeTrue(); // Too high
    
    // Valid rates
    expect(validator(array_merge($baseData, ['conversion_rate' => 0.0001]), $rules)->fails())->toBeFalse(); // Min value
    expect(validator(array_merge($baseData, ['conversion_rate' => 1.0]), $rules)->fails())->toBeFalse(); // Standard value
    expect(validator(array_merge($baseData, ['conversion_rate' => 999999.9999]), $rules)->fails())->toBeFalse(); // Max value
});

it('ignores soft deleted accounts in uniqueness check', function () {
    // Create and soft delete an account
    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Account'
    ]);
    $account->delete(); // Soft delete
    
    // Should be able to create new account with same name
    $rules = Account::getValidationRules($this->user->id);
    $validator = validator([
        'name' => 'Test Account',
        'currency' => 'USD',
        'conversion_rate' => 1.0,
        'user_id' => $this->user->id,
    ], $rules);
    
    expect($validator->fails())->toBeFalse();
});