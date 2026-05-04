<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $recordTitleAttribute = 'description';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chi tiết hoạt động')
                    ->schema([
                        TextEntry::make('log_name')->label('Loại nhật ký'),
                        TextEntry::make('description')->label('Mô tả'),
                        TextEntry::make('subject_type')->label('Đối tượng tác động'),
                        TextEntry::make('subject_id')->label('ID đối tượng'),
                        TextEntry::make('causer.name')->label('Người thực hiện'),
                        TextEntry::make('created_at')->label('Thời gian')->dateTime(),
                        ViewEntry::make('properties')
                            ->view('filament.components.activity-log-properties')
                            ->label('Dữ liệu thay đổi')
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->label('Loại')
                    ->badge()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Mô tả')
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('causer.name')
                    ->label('Người thực hiện')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('log_name')
                    ->label('Lọc theo loại')
                    ->options([
                        'default' => 'Mặc định',
                        'auth' => 'Xác thực',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityLogs::route('/'),
        ];
    }
}
