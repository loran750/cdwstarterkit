<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\AverageRevenuePerUserChart;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class MetricsPage extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'metrics';
    protected string $view = 'filament.admin.pages.metrics-page';


    public function getTitle(): string | Htmlable
    {
        return __('Metrics');
    }

    public static function getNavigationLabel(): string
    {
        return __('Metrics');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('System');
    }


    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make([
                DatePicker::make('start_date')
                    ->default(now()->subYear()->toDateString())
                    ->afterStateHydrated(function (DatePicker $component, ?string $state) {
                        if (! $state) {
                            $component->state(now()->subYear()->toDateString());
                        }
                    })
                    ->label(__('Start Date')),
                DatePicker::make('end_date')
                    ->default(date(now()->toDateString()))
                    ->afterStateHydrated(function (DatePicker $component, ?string $state) {
                        if (! $state) {
                            $component->state(now()->toDateString());
                        }
                    })
                    ->label(__('End Date')),
                Select::make('period')->label(__('Period'))->options([
                    'day' => __('Day'),
                    'week' => __('Week'),
                    'month' => __('Month'),
                    'year' => __('Year'),
                ])->default('month'),

            ])->columnSpanFull()
                ->columns(3),
        ]);
    }
}
