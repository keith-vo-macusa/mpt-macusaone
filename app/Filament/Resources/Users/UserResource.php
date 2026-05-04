<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->disabled(fn (User $record = null) => $record?->id === auth()->id()),
                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last login'),
                TextColumn::make('last_login_ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('roles.name')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                \Filament\Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabled(fn (User $record) => $record->id === auth()->id()),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active status')
                    ->boolean()
                    ->trueLabel('Active users')
                    ->falseLabel('Inactive users')
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make('timeline')
                    ->label('Lịch sử')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->infolist(fn (Schema $schema) => $schema->components([
                        \Filament\Infolists\Components\ViewEntry::make('activities')
                            ->view('filament.components.activity-timeline')
                            ->columnSpanFull()
                    ]))
                    ->modalHeading('Lịch sử hoạt động')
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false),
                DeleteAction::make()
                    ->hidden(fn (User $record) => $record->id === auth()->id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function (User $record) {
                                if ($record->id !== auth()->id()) {
                                    $record->delete();
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
