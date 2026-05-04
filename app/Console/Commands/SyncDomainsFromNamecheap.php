<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\NamecheapService;
use Illuminate\Console\Command;

class SyncDomainsFromNamecheap extends Command
{
    protected $signature = 'domain:sync';
    protected $description = 'Đồng bộ số lượng subdomain từ Namecheap API';

    public function handle(NamecheapService $namecheap)
    {
        $this->info('Bắt đầu đồng bộ Domain từ Namecheap...');
        
        $domains = Domain::where('is_active', true)->get();

        foreach ($domains as $domain) {
            $this->info("Đang kiểm tra: {$domain->domain}");
            
            $hostCount = $namecheap->getHostCount($domain->domain);
            $maxHosts = $namecheap->getMaxHosts($domain->domain);

            $domain->update([
                'subdomains_count' => $hostCount,
                'max_subdomains' => $maxHosts,
            ]);
            
            $this->line(" - Đã tạo: {$hostCount} / Tối đa: {$maxHosts}");
        }

        $this->info('Đồng bộ hoàn tất.');
    }
}
