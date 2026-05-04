<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Vps;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckVpsConnection extends Command
{
    protected $signature = 'vps:check';
    protected $description = 'Kiểm tra trạng thái kết nối của toàn bộ VPS';

    public function handle()
    {
        $this->info('Bắt đầu kiểm tra trạng thái VPS...');
        
        $vpsList = Vps::where('is_active', true)->get();
        $superAdmins = User::role('Super Admin')->get();

        foreach ($vpsList as $vps) {
            $connection = @fsockopen($vps->ip, $vps->port, $errno, $errstr, 2);
            $isOnline = (bool) $connection;
            
            if ($connection) {
                fclose($connection);
            }

            // Nếu trạng thái thay đổi từ Online sang Offline, gửi thông báo
            if ($vps->is_online && !$isOnline) {
                $this->warn("VPS {$vps->name} ({$vps->ip}) bị mất kết nối!");
                
                Notification::make()
                    ->danger()
                    ->title('CẢNH BÁO: VPS MẤT KẾT NỐI')
                    ->body("VPS **{$vps->name}** ({$vps->ip}:{$vps->port}) không thể kết nối vào lúc " . now()->format('H:i d/m/Y'))
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Xem chi tiết')
                            ->url(fn () => \App\Filament\Resources\Vps\VpsResource::getUrl('index')),
                    ])
                    ->sendToDatabase($superAdmins);
            }

            $vps->update([
                'is_online' => $isOnline,
                'last_checked_at' => now(),
            ]);
        }

        $this->info('Đã hoàn thành kiểm tra.');
    }
}
