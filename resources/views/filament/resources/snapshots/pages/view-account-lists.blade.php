<x-filament-panels::page>
    <div class="max-w-md mx-auto space-y-4 px-4 sm:px-6">
        {{-- Header --}}
        <div class="flex items-center justify-between py-4">
            <div class="flex items-center space-x-3">
                <x-filament::button 
                    icon="heroicon-o-arrow-left" 
                    color="gray" 
                    size="sm"
                    href="{{ route('filament.user.resources.snapshots.view', $record) }}"
                >
                </x-filament::button>
                <h1 class="text-xl font-semibold text-gray-900">{{ $this->getTitle() }}</h1>
            </div>
            <x-filament::button 
                icon="heroicon-o-trash" 
                color="gray" 
                size="sm"
            >
            </x-filament::button>
        </div>

        {{-- Cash Section --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between p-4 bg-gray-50">
                <div class="flex items-center space-x-2">
                    <h2 class="text-base font-medium text-gray-900">💰 Cash</h2>
                </div>
                <x-filament::button 
                    size="xs" 
                    color="primary"
                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-sm font-medium"
                >
                    Add
                </x-filament::button>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse($this->getCashAccounts() as $account)
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-gray-900 font-medium">{{ $account['name'] }}</span>
                        <div class="flex items-center space-x-3">
                            <span class="text-sm font-semibold text-gray-900">
                                {{ $account['currency'] }} {{ number_format($account['balance'], 2) }}
                            </span>
                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <button class="p-1.5 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                                        <x-heroicon-o-ellipsis-vertical class="h-5 w-5" />
                                    </button>
                                </x-slot>
                                
                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item>
                                        Edit
                                    </x-filament::dropdown.list.item>
                                    <x-filament::dropdown.list.item>
                                        Delete
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-sm text-gray-500">No cash accounts found</p>
                    </div>
                @endforelse
            </div>

            {{-- Total --}}
            <div class="bg-gray-50 p-4 border-t border-gray-100">
                <div class="flex justify-between items-center">
                    <span class="text-base font-semibold text-gray-900">Total</span>
                    <span class="text-base font-bold text-gray-900">${{ number_format($this->getCashTotal(), 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Mobile-first responsive overrides */
        @media (max-width: 640px) {
            .fi-page {
                padding: 0 !important;
            }
            
            .fi-page > div {
                padding: 0 !important;
            }
        }
    </style>
</x-filament-panels::page>