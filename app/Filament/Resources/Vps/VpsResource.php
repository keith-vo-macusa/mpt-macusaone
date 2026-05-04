<?php

namespace App\Filament\Resources\Vps;

use App\Filament\Resources\Vps\Pages\ManageVps;
use App\Models\Vps;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VpsResource extends Resource
{
    protected static ?string $model = Vps::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | \UnitEnum | null $navigationGroup = 'Systems';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin kết nối')
                    ->description('Thông tin truy cập cơ bản của VPS')
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên VPS')
                            ->placeholder('Ví Giao diện chính')
                            ->required(),
                        TextInput::make('ip')
                            ->label('Địa chỉ IP')
                            ->placeholder('160.191.89.42')
                            ->required()
                            ->ipv4()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get) {
                                return $rule->where('port', $get('port'));
                            })
                            ->validationMessages([
                                'unique' => 'Tổ hợp IP và Cổng này đã tồn tại trong hệ thống.',
                            ]),
                        TextInput::make('port')
                            ->label('Cổng (Port)')
                            ->required()
                            ->numeric()
                            ->default(22)
                            ->live(),
                        
                        Actions::make([
                            Action::make('test_connection_form')
                                ->label('Kiểm tra kết nối ngay')
                                ->icon('heroicon-m-signal')
                                ->color('success')
                                ->action(function (Get $get) {
                                    $ip = $get('ip');
                                    $port = $get('port');

                                    if (!$ip || !$port) {
                                        Notification::make()
                                            ->warning()
                                            ->title('Thiếu thông tin!')
                                            ->body('Vui lòng nhập đầy đủ IP và Cổng trước khi kiểm tra.')
                                            ->send();
                                        return;
                                    }

                                    $connection = @fsockopen($ip, $port, $errno, $errstr, 2);

                                    if ($connection) {
                                        fclose($connection);
                                        Notification::make()
                                            ->success()
                                            ->title('Kết nối thành công!')
                                            ->body("VPS tại {$ip}:{$port} đang phản hồi tốt.")
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->danger()
                                            ->title('Kết nối thất bại!')
                                            ->body("Không thể kết nối tới {$ip}:{$port}. Lỗi: {$errstr}")
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull(),

                        TextInput::make('username')
                            ->label('Tên đăng nhập')
                            ->default('root')
                            ->required(),
                        TextInput::make('password')
                            ->label('Mật khẩu')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])->columns(2),

                Section::make('Bổ sung')
                    ->schema([
                        Textarea::make('note')
                            ->label('Ghi chú')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Kích hoạt')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('ip'),
                TextEntry::make('port')
                    ->numeric(),
                TextEntry::make('username'),
                TextEntry::make('note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Tên VPS')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip')
                    ->label('Địa chỉ IP')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Đã sao chép IP')
                    ->fontFamily('mono'),
                TextColumn::make('port')
                    ->label('Cổng')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('username')
                    ->label('User')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Kích hoạt')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('is_online')
                    ->label('Kết nối')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Online' : 'Offline')
                    ->sortable(),
                TextColumn::make('last_checked_at')
                    ->label('Kiểm tra cuối')
                    ->dateTime('H:i d/m')
                    ->description(fn (Vps $record) => $record->last_checked_at?->diffForHumans())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('test_connection')
                    ->label('Test')
                    ->icon('heroicon-m-signal')
                    ->color('success')
                    ->action(function (Vps $record) {
                        $connection = @fsockopen($record->ip, $record->port, $errno, $errstr, 2);

                        if ($connection) {
                            fclose($connection);
                            Notification::make()
                                ->success()
                                ->title('Kết nối thành công!')
                                ->body("VPS {$record->name} ({$record->ip}:{$record->port}) đang hoạt động tốt.")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Kết nối thất bại!')
                                ->body("Lỗi: {$errstr} ({$errno})")
                                ->send();
                        }
                    }),
                ViewAction::make(),
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
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVps::route('/'),
        ];
    }
}
