<?php

namespace App\Filament\Admin\Resources\Webhooks;

use App\Constants\EventConstants;
use App\Filament\Admin\Resources\Webhooks\Pages\CreateWebhook;
use App\Filament\Admin\Resources\Webhooks\Pages\EditWebhook;
use App\Filament\Admin\Resources\Webhooks\Pages\ListWebhooks;
use App\Models\Webhook;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WebhookResource extends Resource
{
    protected static ?string $model = Webhook::class;

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return __('System');
    }

    public static function getNavigationLabel(): string
    {
        return __('Webhooks');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Webhook Details'))
                    ->description(__('Configure your webhook endpoint to receive real-time event notifications'))
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label(__('Webhook Name'))
                            ->helperText(__('A friendly name to identify this webhook'))
                            ->maxLength(255),

                        TextInput::make('url')
                            ->required()
                            ->label(__('Webhook URL'))
                            ->url()
                            ->helperText(__('The endpoint URL where webhook events will be sent'))
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label(__('Description'))
                            ->helperText(__('Optional description of what this webhook is used for'))
                            ->rows(3),

                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->helperText(__('Enable or disable this webhook'))
                            ->default(true),
                    ])->columnSpanFull(),

                Section::make(__('Event Configuration'))
                    ->description(__('Select which events should trigger this webhook'))
                    ->schema([
                        Select::make('events')
                            ->required()
                            ->multiple()
                            ->label(__('Events to Subscribe'))
                            ->helperText(__('Select one or more events that will trigger this webhook. Use "*" to subscribe to all events.'))
                            ->options([
                                '*' => __('All Events'),
                                __('Custom Events') => EventConstants::getAllEvents()
                            ])
                            ->searchable()
                            ->preload(),
                    ])->columnSpanFull(),

                Section::make(__('Advanced Settings'))
                    ->description(__('Configure retry behavior and custom headers'))
                    ->schema([
                        TextInput::make('max_retries')
                            ->numeric()
                            ->label(__('Max Retries'))
                            ->helperText(__('Maximum number of retry attempts for failed deliveries'))
                            ->default(3)
                            ->minValue(0)
                            ->maxValue(10),

                        TextInput::make('timeout')
                            ->numeric()
                            ->label(__('Timeout (seconds)'))
                            ->helperText(__('Request timeout in seconds'))
                            ->default(30)
                            ->minValue(5)
                            ->maxValue(120),

                        KeyValue::make('headers')
                            ->label(__('Custom Headers'))
                            ->helperText(__('Add custom HTTP headers to include with webhook requests (e.g., Authorization, X-Custom-Header)'))
                            ->keyLabel(__('Header Name'))
                            ->valueLabel(__('Header Value'))
                            ->reorderable(),

                        TextInput::make('secret')
                            ->label(__('Webhook Secret'))
                            ->helperText(__('Auto-generated secret key for webhook signature verification. Save this securely!'))
                            ->default(fn() => Str::random(32))
                            ->readOnly()
                            ->dehydrated()
                            ->maxLength(255),
                    ])->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(__('Manage your webhook endpoints'))
            ->description(__('Webhooks allow you to receive real-time notifications about events in your application'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('Name'))
                    ->description(fn(Webhook $record): string => $record->url),

                TextColumn::make('events')
                    ->label(__('Events'))
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->tooltip(fn(Webhook $record): string => implode(', ', $record->events ?? [])),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                TextColumn::make('success_count')
                    ->label(__('Success'))
                    ->sortable()
                    ->alignCenter()
                    ->color('success'),

                TextColumn::make('failure_count')
                    ->label(__('Failures'))
                    ->sortable()
                    ->alignCenter()
                    ->color('danger'),

                TextColumn::make('last_triggered_at')
                    ->label(__('Last Triggered'))
                    ->dateTime(config('app.datetime_format'))
                    ->sortable()
                    ->placeholder(__('Never')),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime(config('app.datetime_format'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhooks::route('/'),
            'create' => CreateWebhook::route('/create'),
            'edit' => EditWebhook::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Webhook');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Webhooks');
    }
}
