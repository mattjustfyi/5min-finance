<?php

use App\Livewire\AddAccountModal;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render the add account modal component', function () {
    Livewire::test(AddAccountModal::class)
        ->assertStatus(200)
        ->assertSee('Add New Account');
});

it('can open and close the modal', function () {
    Livewire::test(AddAccountModal::class)
        ->assertSet('showModal', false)
        ->call('openModal')
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false);
});

it('resets form when opening modal', function () {
    Livewire::test(AddAccountModal::class)
        ->set('name', 'Test Account')
        ->set('currency', 'EUR')
        ->set('conversionRate', 1.5)
        ->call('openModal')
        ->assertSet('name', '')
        ->assertSet('currency', 'USD')
        ->assertSet('conversionRate', 1.0);
});

it('validates required fields', function () {
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', '')
        ->set('currency', '')
        ->set('conversionRate', 0)
        ->call('createAccount')
        ->assertHasErrors(['name', 'currency', 'conversionRate']);
});

it('validates name minimum length', function () {
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', '')
        ->set('currency', 'USD')
        ->set('conversionRate', 1.0)
        ->call('createAccount')
        ->assertHasErrors(['name']);
});

it('validates currency format', function () {
    // Test too short currency
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'US')
        ->set('conversionRate', 1.0)
        ->call('createAccount')
        ->assertHasErrors(['currency']);
    
    // Test too long currency
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'USDX')
        ->set('conversionRate', 1.0)
        ->call('createAccount')
        ->assertHasErrors(['currency']);
    
    // Test valid currency
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'USD')
        ->set('conversionRate', 1.0)
        ->call('createAccount')
        ->assertHasNoErrors(['currency']);
});

it('validates conversion rate bounds', function () {
    // Test zero conversion rate
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'USD')
        ->set('conversionRate', 0)
        ->call('createAccount')
        ->assertHasErrors(['conversionRate']);
    
    // Test negative conversion rate
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'USD')
        ->set('conversionRate', -1)
        ->call('createAccount')
        ->assertHasErrors(['conversionRate']);
    
    // Test valid conversion rate (minimum)
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'USD')
        ->set('conversionRate', 0.0001)
        ->call('createAccount')
        ->assertHasNoErrors(['conversionRate']);
});

it('creates account with valid data', function () {
    expect(Account::count())->toBe(0);
    
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'USD')
        ->set('conversionRate', 1.0)
        ->call('createAccount')
        ->assertSet('showModal', false)
        ->assertDispatched('account-created');
    
    expect(Account::count())->toBe(1);
    
    $account = Account::first();
    expect($account->name)->toBe('Test Account');
    expect($account->currency)->toBe('USD');
    expect((float) $account->conversion_rate)->toBe(1.0);
    expect($account->user_id)->toBe($this->user->id);
});

it('prevents duplicate account names', function () {
    // Create existing account
    Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Existing Account'
    ]);
    
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Existing Account')
        ->set('currency', 'USD')
        ->set('conversionRate', 1.0)
        ->call('createAccount')
        ->assertHasErrors(['name']);
    
    expect(Account::count())->toBe(1); // No new account created
});

it('resets form after successful creation', function () {
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'EUR')
        ->set('conversionRate', 1.5)
        ->call('createAccount')
        ->assertSet('name', '')
        ->assertSet('currency', 'USD')
        ->assertSet('conversionRate', 1.0);
});

it('emits event with account id on successful creation', function () {
    Livewire::test(AddAccountModal::class)
        ->set('showModal', true)
        ->set('name', 'Test Account')
        ->set('currency', 'USD')
        ->set('conversionRate', 1.0)
        ->call('createAccount')
        ->assertDispatched('account-created', function ($event, $data) {
            $account = Account::first();
            return isset($data['accountId']) && $data['accountId'] === $account->id;
        });
});