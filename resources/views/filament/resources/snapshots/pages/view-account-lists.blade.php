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

            @php
                $accounts = $this->getCashAccounts();
                $total = $this->getCashTotal();
            @endphp
            
            @if(count($accounts) > 0)
                {{-- Filament-styled table --}}
                <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    <span class="group flex w-full items-center justify-start gap-x-1">
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                            Account Name
                                        </span>
                                    </span>
                                </th>
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    <span class="group flex w-full items-center justify-end gap-x-1">
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                            Balance
                                        </span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @foreach($accounts as $account)
                                <tr class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                        <div class="fi-ta-col-wrp px-3 py-4">
                                            <div class="fi-ta-text grid w-full gap-y-1">
                                                <div class="flex w-max">
                                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 px-0 py-0 text-sm leading-6 text-gray-950 dark:text-white font-medium">
                                                        {{ $account['name'] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                        <div class="fi-ta-col-wrp px-3 py-4 text-end">
                                            <div class="fi-ta-text grid w-full gap-y-1">
                                                <div class="flex w-max justify-end">
                                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 px-0 py-0 text-sm leading-6 text-gray-950 dark:text-white font-semibold">
                                                        {{ $account['currency'] }} {{ number_format($account['balance'], 2) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            
                            {{-- Total Row --}}
                            <tr class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 bg-gray-50 dark:bg-white/5 border-t-2 border-gray-300 dark:border-white/10">
                                <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                    <div class="fi-ta-col-wrp px-3 py-4">
                                        <div class="fi-ta-text grid w-full gap-y-1">
                                            <div class="flex w-max">
                                                <div class="fi-ta-text-item inline-flex items-center gap-1.5 px-0 py-0 text-sm leading-6 text-gray-950 dark:text-white font-bold">
                                                    Total
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                    <div class="fi-ta-col-wrp px-3 py-4 text-end">
                                        <div class="fi-ta-text grid w-full gap-y-1">
                                            <div class="flex w-max justify-end">
                                                <div class="fi-ta-text-item inline-flex items-center gap-1.5 px-0 py-0 text-sm leading-6 text-gray-950 dark:text-white font-bold">
                                                    ${{ number_format($total, 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="fi-ta-empty-state px-6 py-12">
                    <div class="fi-ta-empty-state-content mx-auto grid max-w-lg justify-items-center text-center">
                        <div class="fi-ta-empty-state-description text-sm text-gray-500 dark:text-gray-400">
                            No cash accounts found
                        </div>
                    </div>
                </div>
            @endif
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