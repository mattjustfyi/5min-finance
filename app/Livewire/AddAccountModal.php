<?php

namespace App\Livewire;

use App\Models\Account;
use App\Services\AccountService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AddAccountModal extends Component
{
    public bool $showModal = false;
    public string $name = '';
    public string $currency = 'USD';
    public float $conversionRate = 1.0;

    protected AccountService $accountService;

    public function boot(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function rules()
    {
        return Account::getValidationRules(Filament::auth()->id());
    }

    public function messages()
    {
        return [
            'name.required' => 'Account name is required.',
            'name.min' => 'Account name must be at least 1 character.',
            'name.unique' => 'You already have an account with this name.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be exactly 3 characters (e.g., USD, EUR).',
            'conversionRate.required' => 'Conversion rate is required.',
            'conversionRate.numeric' => 'Conversion rate must be a number.',
            'conversionRate.min' => 'Conversion rate must be greater than 0.',
        ];
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function createAccount()
    {
        try {
            // Validate using custom validation rules
            $validatedData = $this->validate([
                'name' => $this->rules()['name'],
                'currency' => $this->rules()['currency'],
                'conversionRate' => $this->rules()['conversion_rate'],
            ]);

            $account = $this->accountService->createAccount([
                'name' => $validatedData['name'],
                'currency' => $validatedData['currency'],
                'conversion_rate' => $validatedData['conversionRate'],
            ], Filament::auth()->id());

            $this->closeModal();

            // Emit event to parent component
            $this->dispatch('account-created', accountId: $account->id);

            Notification::make()
                ->title('Account created!')
                ->body("Account '{$account->name}' has been created successfully.")
                ->success()
                ->send();

        } catch (ValidationException $e) {
            // Validation errors will be handled by Livewire
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error creating account')
                ->body('An unexpected error occurred. Please try again.')
                ->danger()
                ->send();
        }
    }

    private function resetForm()
    {
        $this->name = '';
        $this->currency = 'USD';
        $this->conversionRate = 1.0;
    }

    public function updated($propertyName)
    {
        // Map property names for validation
        $validationRules = [
            'name' => $this->rules()['name'],
            'currency' => $this->rules()['currency'],
            'conversionRate' => $this->rules()['conversion_rate'],
        ];
        
        if (isset($validationRules[$propertyName])) {
            $this->validateOnly($propertyName, [$propertyName => $validationRules[$propertyName]]);
        }
    }

    public function render()
    {
        return view('livewire.add-account-modal');
    }
}
