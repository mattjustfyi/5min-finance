<x-filament-panels::page>
    <div>
        {{-- Cash Section --}}
        <div>
            <div>
                <h2 class="!text-xl !font-bold !text-gray-900 dark:!text-white">💰 Cash</h2>
                <x-filament::button 
                    size="xs" 
                    color="primary"
                    class=""
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
                <div class="fi-ta-ctn">
                    <table class="fi-ta-table">
                        <thead>
                            <tr>
                                <th class="fi-ta-header-cell">
                                    <span>
                                        <span class="fi-ta-header-cell-label">
                                            Account Name
                                        </span>
                                    </span>
                                </th>
                                <th class="fi-ta-header-cell">
                                    <span>
                                        <span class="fi-ta-header-cell-label">
                                            Balance
                                        </span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $account)
                                <tr class="fi-ta-row">
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col-wrp">
                                            <div class="fi-ta-text">
                                                <div>
                                                    <div class="fi-ta-text-item">
                                                        {{ $account['name'] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col-wrp">
                                            <div class="fi-ta-text">
                                                <div>
                                                    <div class="fi-ta-text-item">
                                                        {{ $account['currency'] }} {{ number_format($account['balance'], 2) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            
                            {{-- Total Row --}}
                            <tr class="fi-ta-row">
                                <td class="fi-ta-cell">
                                    <div class="fi-ta-col-wrp">
                                        <div class="fi-ta-text">
                                            <div>
                                                <div class="fi-ta-text-item">
                                                    Total
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fi-ta-cell">
                                    <div class="fi-ta-col-wrp">
                                        <div class="fi-ta-text">
                                            <div>
                                                <div class="fi-ta-text-item">
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
                <div class="fi-ta-empty-state">
                    <div class="fi-ta-empty-state-content">
                        <div class="fi-ta-empty-state-description">
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