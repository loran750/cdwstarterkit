<x-filament-panels::page>
    <div>
        {{ $this->filtersForm }}
    </div>
    <div class="grid grid-cols-6 gap-8">
        <div class="col-span-6">
            @livewire(\App\Filament\Admin\Widgets\UserRegisteredCard::class, [
                'pageFilters' => $this->filters
            ])
        </div>
        <div class="col-span-3">
            @livewire(\App\Filament\Admin\Widgets\TotalRevenueChart::class, [
                'pageFilters' => $this->filters
            ])
        </div>
        <div class="col-span-3">
                @livewire(\App\Filament\Admin\Widgets\AverageRevenuePerUserChart::class, [
                    'pageFilters' => $this->filters
                ])
        </div>
        <div class="col-span-3">
            @livewire(\App\Filament\Admin\Widgets\MonthlyRecurringRevenueChart::class, [
                'pageFilters' => $this->filters
            ])
        </div>
    </div>
</x-filament-panels::page>
