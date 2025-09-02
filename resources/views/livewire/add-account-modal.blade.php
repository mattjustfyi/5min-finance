<div>
    {{-- Modal Backdrop --}}
    <div 
        x-data="{ show: @entangle('showModal') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
        @keydown.escape.window="$wire.closeModal()"
    >
        {{-- Background overlay --}}
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div 
                class="fixed inset-0 bg-gray-500/75 transition-opacity dark:bg-gray-900/75"
                @click="$wire.closeModal()"
            ></div>

            {{-- Modal --}}
            <div 
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:align-middle"
                @click.stop
            >
                {{-- Modal Header --}}
                <div class="mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        Add New Account
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Create a new account to track your finances.
                    </p>
                </div>

                {{-- Modal Form --}}
                <form wire:submit="createAccount" class="space-y-4">
                    {{-- Account Name Field --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Account Name <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    wire:model.live="name"
                                    id="name"
                                    type="text"
                                    placeholder="Enter account name"
                                    autofocus
                                    class="block w-full"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Currency Field --}}
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Currency <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    wire:model.live="currency"
                                    id="currency"
                                    type="text"
                                    placeholder="USD"
                                    maxlength="3"
                                    class="block w-full uppercase"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            3-letter currency code (e.g., USD, EUR, GBP)
                        </p>
                    </div>

                    {{-- Conversion Rate Field --}}
                    <div>
                        <label for="conversionRate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Conversion Rate <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    wire:model.live="conversionRate"
                                    id="conversionRate"
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    max="999999.9999"
                                    placeholder="1.0000"
                                    class="block w-full"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        @error('conversionRate')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Rate to convert this currency to your base currency (1.0 for base currency)
                        </p>
                    </div>

                    {{-- Modal Actions --}}
                    <div class="mt-6 flex justify-end space-x-3">
                        <x-filament::button
                            type="button"
                            color="gray"
                            wire:click="closeModal"
                        >
                            Cancel
                        </x-filament::button>
                        
                        <x-filament::button
                            type="submit"
                            color="primary"
                            wire:loading.attr="disabled"
                            wire:target="createAccount"
                        >
                            <span wire:loading.remove wire:target="createAccount">
                                Create Account
                            </span>
                            <span wire:loading wire:target="createAccount">
                                Creating...
                            </span>
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
