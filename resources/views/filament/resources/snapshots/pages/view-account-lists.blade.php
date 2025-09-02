<x-filament-panels::page>
    <div>
        {{-- Cash Section --}}
        <div>
            <div>
                <h2 class="!text-xl !font-bold !text-gray-900 dark:!text-white">💰 Cash</h2>
                {{ $this->addAccountAction }}
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
                                <th class="fi-ta-header-cell">
                                    <span>
                                        <span class="fi-ta-header-cell-label">
                                            Actions
                                        </span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $account)
                                <tr class="fi-ta-row" wire:key="account-row-{{ $account['id'] }}">
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
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-600">{{ $account['currency'] }}</span>
                                                <x-filament::input.wrapper>
                                                    <x-filament::input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        wire:key="account-input-{{ $account['id'] }}"
                                                        wire:model.blur="accountBalances.{{ $account['id'] }}"
                                                        class="text-right"
                                                        style="min-width: 120px;"
                                                    />
                                                </x-filament::input.wrapper>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-col-wrp">
                                            <div class="flex justify-end">
                                                {{ ($this->deleteAccountAction)(['accountId' => $account['id'], 'accountName' => $account['name']]) }}
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
                                                    ${{ number_format($this->cashTotal, 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fi-ta-cell">
                                    {{-- Empty cell for actions column --}}
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

    <x-filament-actions::modals />
</x-filament-panels::page>
