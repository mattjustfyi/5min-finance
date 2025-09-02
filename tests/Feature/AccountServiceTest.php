<?php

use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use App\Enums\RetirementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->accountService = new AccountService();
});

it('creates account successfully with valid data', function () {
    $data = [
        'name' => 'Test Account',
        'currency' => 'USD',
        'conversion_rate' => 1.0000,
    ];
    
    $account = $this->accountService->createAccount($data, $this->user->id);
    
    expect($account)->toBeInstanceOf(Account::class);
    expect($account->name)->toBe('Test Account');
    expect($account->currency)->toBe('USD');
    expect((float) $account->conversion_rate)->toBe(1.0);
    expect($account->retirement_type)->toBe(RetirementType::NON_RETIREMENT);
    expect($account->user_id)->toBe($this->user->id);
});

it('defaults to non_retirement when retirement_type not specified', function () {
    $data = [
        'name' => 'Test Account',
        'currency' => 'USD',
        'conversion_rate' => 1.0000,
    ];
    
    $account = $this->accountService->createAccount($data, $this->user->id);
    
    expect($account->retirement_type)->toBe(RetirementType::NON_RETIREMENT);
});

it('respects explicit retirement_type', function () {
    $data = [
        'name' => 'Test Account',
        'currency' => 'USD',
        'conversion_rate' => 1.0000,
        'retirement_type' => RetirementType::RETIREMENT,
    ];
    
    $account = $this->accountService->createAccount($data, $this->user->id);
    
    expect($account->retirement_type)->toBe(RetirementType::RETIREMENT);
});

it('throws validation exception for invalid data', function () {
    $data = [
        'name' => '', // Invalid: empty name
        'currency' => 'USD',
        'conversion_rate' => 1.0000,
    ];
    
    expect(fn() => $this->accountService->createAccount($data, $this->user->id))
        ->toThrow(ValidationException::class);
});

it('throws validation exception for duplicate name', function () {
    // Create first account
    Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Duplicate Name',
    ]);
    
    $data = [
        'name' => 'Duplicate Name',
        'currency' => 'USD',
        'conversion_rate' => 1.0000,
    ];
    
    expect(fn() => $this->accountService->createAccount($data, $this->user->id))
        ->toThrow(ValidationException::class);
});

it('can check if account name is available', function () {
    // Initially available
    expect($this->accountService->canCreateAccount('New Account', $this->user->id))
        ->toBeTrue();
    
    // Create account
    Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'New Account',
    ]);
    
    // No longer available
    expect($this->accountService->canCreateAccount('New Account', $this->user->id))
        ->toBeFalse();
});

it('allows same name for different users', function () {
    $user2 = User::factory()->create();
    
    // Create account for first user
    Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Same Name',
    ]);
    
    // Should be available for second user
    expect($this->accountService->canCreateAccount('Same Name', $user2->id))
        ->toBeTrue();
    
    $data = [
        'name' => 'Same Name',
        'currency' => 'USD',
        'conversion_rate' => 1.0000,
    ];
    
    $account = $this->accountService->createAccount($data, $user2->id);
    expect($account->name)->toBe('Same Name');
    expect($account->user_id)->toBe($user2->id);
});

it('validates account data without creating account', function () {
    $data = [
        'name' => 'Valid Account',
        'currency' => 'USD',
        'conversion_rate' => 1.0000,
    ];
    
    $validatedData = $this->accountService->validateAccountData($data, $this->user->id);
    
    expect($validatedData)->toHaveKeys(['name', 'currency', 'conversion_rate', 'retirement_type', 'user_id']);
    expect($validatedData['name'])->toBe('Valid Account');
    expect($validatedData['user_id'])->toBe($this->user->id);
    expect($validatedData['retirement_type'])->toBe(RetirementType::NON_RETIREMENT);
});

it('throws validation exception for invalid validation data', function () {
    $data = [
        'name' => 'Valid Name',
        'currency' => 'INVALID', // Invalid: wrong length
        'conversion_rate' => 1.0000,
    ];
    
    expect(fn() => $this->accountService->validateAccountData($data, $this->user->id))
        ->toThrow(ValidationException::class);
});

it('ignores soft deleted accounts in name uniqueness', function () {
    // Create and soft delete an account
    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Deleted Account',
    ]);
    $account->delete();
    
    // Should be able to create new account with same name
    expect($this->accountService->canCreateAccount('Deleted Account', $this->user->id))
        ->toBeTrue();
    
    $data = [
        'name' => 'Deleted Account',
        'currency' => 'USD',
        'conversion_rate' => 1.0000,
    ];
    
    $newAccount = $this->accountService->createAccount($data, $this->user->id);
    expect($newAccount->name)->toBe('Deleted Account');
});