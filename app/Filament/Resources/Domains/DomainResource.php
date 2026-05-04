<?php

namespace App\Filament\Resources\Domains;

use App\Filament\Resources\Domains\Pages\ManageDomains;
use App\Models\Domain;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static ?string $recordTitleAttribute = 'domain';

    protected static string | \UnitEnum | null $navigationGroup = 'Systems';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin Domain')
                    ->schema([
                        TextInput::make('domain')
                            ->label('Tên Miền')
                            ->placeholder('example.com')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('priority')
                            ->label('Độ ưu tiên')
                            ->numeric()
                            ->default(0)
                            ->helperText('Số càng lớn độ ưu tiên càng cao'),
                        Toggle::make('is_active')
                            ->label('Trạng thái kích hoạt')
                            ->default(true),
                    ])->columns(2),

                Section::make('Cấu hình Subdomain')
                    ->description('Quản lý giới hạn số lượng subdomain cho tên miền này')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('max_subdomains')
                                    ->label('Giới hạn tối đa')
                                    ->numeric()
                                    ->default(100)
                                    ->required(),
                                TextInput::make('subdomains_count')
                                    ->label('Đã tạo')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false), // Chỉ hiển thị, sẽ được cập nhật bởi hệ thống sau
                                TextInput::make('remaining_subdomains')
                                    ->label('Còn lại')
                                    ->numeric()
                                    ->default(fn (?Domain $record) => $record?->remaining_subdomains ?? 100)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('domain'),
                TextEntry::make('max_subdomains')
                    ->numeric(),
                TextEntry::make('subdomains_count')
                    ->numeric(),
                TextEntry::make('priority')
                    ->numeric(),
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
            ->defaultSort('priority', 'desc')
            ->columns([
                TextColumn::make('domain')
                    ->label('Tên Miền')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('max_subdomains')
                    ->label('Tối đa')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('subdomains_count')
                    ->label('Đã dùng')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('warning'),
                TextColumn::make('remaining_subdomains')
                    ->label('Còn lại')
                    ->numeric()
                    ->getStateUsing(fn (Domain $record) => $record->remaining_subdomains)
                    ->sortable()
                    ->alignCenter()
                    ->color('success')
                    ->weight('bold'),
                TextColumn::make('priority')
                    ->label('Ưu tiên')
                    ->numeric()
                    ->sortable()
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Trạng thái')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('add_subdomain')
                    ->label('Thêm Subdomain')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('host')
                            ->label('Host (ví dụ: test, vps1)')
                            ->required(),
                        \Filament\Forms\Components\Select::make('type')
                            ->label('Loại bản ghi')
                            ->options([
                                'A' => 'A (IPv4)',
                                'AAAA' => 'AAAA (IPv6)',
                                'CNAME' => 'CNAME (Alias)',
                                'TXT' => 'TXT (Text)',
                            ])
                            ->default('A')
                            ->required(),
                        TextInput::make('address')
                            ->label('Giá trị / IP')
                            ->required(),
                    ])
                    ->action(function (Domain $record, array $data, \App\Services\NamecheapService $namecheap) {
                        $success = $namecheap->addHost(
                            $record->domain,
                            $data['host'],
                            $data['type'],
                            $data['address']
                        );

                        if ($success) {
                            // Tự động cập nhật lại số lượng mới
                            $hostCount = $namecheap->getHostCount($record->domain);
                            $maxHosts = $namecheap->getMaxHosts($record->domain);

                            $record->update([
                                'subdomains_count' => $hostCount,
                                'max_subdomains' => $maxHosts,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Đã thêm Subdomain!')
                                ->body("Subdomain {$data['host']}.{$record->domain} đã được tạo thành công.")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Lỗi!')
                                ->body('Không thể tạo subdomain. Vui lòng kiểm tra lại cấu hình API hoặc Log.')
                                ->send();
                        }
                    }),
                Action::make('view_subdomains')
                    ->label('DS Subdomain')
                    ->icon('heroicon-m-list-bullet')
                    ->color('info')
                    ->modalHeading(fn (Domain $record) => "Danh sách Subdomain: {$record->domain}")
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (Domain $record, \App\Services\NamecheapService $namecheap) => new \Illuminate\Support\HtmlString(
                        \Illuminate\Support\Facades\Blade::render('
                            <div x-data="{ 
                                search: \'\', 
                                hosts: {{ json_encode($namecheap->getHosts($record->domain)) }},
                                get filtered() {
                                    if (!this.search) return this.hosts;
                                    let s = this.search.toLowerCase();
                                    return this.hosts.filter(h => 
                                        h.Host.toLowerCase().includes(s) || 
                                        h.Type.toLowerCase().includes(s) || 
                                        h.Address.toLowerCase().includes(s)
                                    );
                                }
                            }" style="display: flex; flex-direction: column; gap: 1rem;">
                                <!-- Search Input Wrapper -->
                                <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.5rem; background: rgba(128,128,128,0.1); border: 1px solid rgba(128,128,128,0.2);">
                                    <div style="padding: 0 0.5rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <input x-model="search" placeholder="Tìm kiếm nhanh..." style="width: 100%; background: transparent; border: none; padding: 0; font-size: 0.875rem; outline: none; color: inherit;">
                                </div>

                                <!-- Table Wrapper -->
                                <div style="border: 1px solid rgba(128,128,128,0.2); border-radius: 0.75rem; overflow: hidden; background: transparent;">
                                    <!-- Header -->
                                    <div style="display: flex; background: rgba(128,128,128,0.05); border-bottom: 1px solid rgba(128,128,128,0.2); padding: 0.75rem 1rem; font-weight: bold; font-size: 0.75rem; text-transform: uppercase; color: #6b7280;">
                                        <div style="width: 25%;">Host</div>
                                        <div style="width: 20%;">Loại</div>
                                        <div style="flex: 1;">Giá trị / Đích</div>
                                    </div>

                                    <!-- Body -->
                                    <div style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column;">
                                        <template x-for="h in filtered">
                                            <div style="display: flex; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid rgba(128,128,128,0.1);">
                                                <div style="width: 25%; font-family: monospace; font-weight: bold; color: #3b82f6;" x-text="h.Host"></div>
                                                <div style="width: 20%;">
                                                    <span style="padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.625rem; font-weight: bold; border: 1px solid rgba(128,128,128,0.3);" x-text="h.Type"></span>
                                                </div>
                                                <div style="flex: 1; font-size: 0.875rem; word-break: break-all; opacity: 0.8;" x-text="h.Address"></div>
                                            </div>
                                        </template>
                                        
                                        <!-- Empty -->
                                        <div x-show="filtered.length === 0" style="padding: 2rem; text-align: center; color: #9ca3af; font-style: italic; font-size: 0.875rem;">
                                            Không có dữ liệu...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ', ['record' => $record, 'namecheap' => $namecheap])
                    )),
                Action::make('timeline')
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
                Action::make('sync_namecheap')
                    ->label('Đồng bộ')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->action(function (Domain $record, \App\Services\NamecheapService $namecheap) {
                        $hostCount = $namecheap->getHostCount($record->domain);
                        $maxHosts = $namecheap->getMaxHosts($record->domain);

                        $record->update([
                            'subdomains_count' => $hostCount,
                            'max_subdomains' => $maxHosts,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Đồng bộ thành công!')
                            ->body("Domain {$record->domain} đã được cập nhật dữ liệu từ Namecheap.")
                            ->send();
                    }),
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
            'index' => ManageDomains::route('/'),
        ];
    }
}
