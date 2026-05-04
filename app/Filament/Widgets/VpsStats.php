<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Vps;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VpsStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalVps = Vps::count();
        $onlineVps = Vps::where('is_online', true)->where('is_active', true)->count();
        $offlineVps = Vps::where('is_online', false)->where('is_active', true)->count();
        $totalUsers = User::count();
        $totalDomains = \App\Models\Domain::count();

        return [
            Stat::make('Tổng số VPS', $totalVps)
                ->description('Số lượng VPS trong hệ thống')
                ->descriptionIcon('heroicon-m-server-stack')
                ->color('info'),
            Stat::make('Tổng Domain', $totalDomains)
                ->description('Số lượng tên miền chính')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),
            Stat::make('VPS đang Online', $onlineVps)
->description('Số VPS hoạt động bình thường')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('VPS đang Offline', $offlineVps)
                ->description('Số VPS mất kết nối')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($offlineVps > 0 ? 'danger' : 'success'),
            Stat::make('Người quản trị', $totalUsers)
                ->description('Tổng số tài khoản Admin')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
