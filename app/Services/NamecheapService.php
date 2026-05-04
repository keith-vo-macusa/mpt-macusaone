<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NamecheapService
{
    protected string $baseUrl;
    protected string $apiUser;
    protected string $apiKey;
    protected string $clientIp;

    public function __construct()
    {
        $this->baseUrl = config('services.namecheap.sandbox') 
            ? 'https://api.sandbox.namecheap.com/xml.response' 
            : 'https://api.namecheap.com/xml.response';
        
        $this->apiUser = config('services.namecheap.user');
        $this->apiKey = config('services.namecheap.key');
        $this->clientIp = config('services.namecheap.ip');
    }

    public function getHostCount(string $domain): int
    {
        if (config('services.namecheap.mock')) {
            return rand(5, 50); // Trả về số ngẫu nhiên để test giao diện
        }

        try {
            $parts = explode('.', $domain);
            if (count($parts) < 2) return 0;

            $tld = array_pop($parts);
            $sld = implode('.', $parts);

            $response = Http::get($this->baseUrl, [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->apiUser,
                'Command' => 'namecheap.domains.dns.getHosts',
                'ClientIp' => $this->clientIp,
                'SLD' => $sld,
                'TLD' => $tld,
            ]);

            if ($response->failed()) {
                Log::error("Namecheap API error for {$domain}: " . $response->body());
                return 0;
            }

            $xml = simplexml_load_string($response->body());
            if (!$xml || (string) $xml['Status'] === 'ERROR') {
                Log::error("Namecheap API XML error for {$domain}: " . ($xml->Errors->Error ?? 'Unknown error'));
                return 0;
            }

            // Đếm số lượng thẻ host trong kết quả trả về
            $hostCount = count($xml->CommandResponse->DomainDNSGetHostsResult->host);
            
            return $hostCount;
        } catch (\Exception $e) {
            Log::error("Namecheap Service Exception: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Namecheap thường giới hạn 150 hosts cho BasicDNS/PremiumDNS.
     * Tùy vào gói cước mà con số này có thể thay đổi.
     */
    public function getMaxHosts(string $domain): int
    {
        return 150; // Giả định mặc định của Namecheap là 150
    }

    public function getHosts(string $domain): array
    {
        if (config('services.namecheap.mock')) {
            return [
                ['Host' => '@', 'Type' => 'A', 'Address' => '1.2.3.4', 'TTL' => '60'],
                ['Host' => 'www', 'Type' => 'CNAME', 'Address' => $domain, 'TTL' => '60'],
                ['Host' => 'mail', 'Type' => 'A', 'Address' => '5.6.7.8', 'TTL' => '60'],
            ];
        }

        try {
            $parts = explode('.', $domain);
            if (count($parts) < 2) return [];

            $tld = array_pop($parts);
            $sld = implode('.', $parts);

            $response = Http::get($this->baseUrl, [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->apiUser,
                'Command' => 'namecheap.domains.dns.getHosts',
                'ClientIp' => $this->clientIp,
                'SLD' => $sld,
                'TLD' => $tld,
            ]);

            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error("Namecheap API Connection Failed for {$domain}: Status " . $response->status());
                return [];
            }

            $xml = simplexml_load_string($response->body());
            
            if (!$xml) {
                \Illuminate\Support\Facades\Log::error("Namecheap API Invalid XML response for {$domain}");
                return [];
            }

            if ((string) $xml['Status'] === 'ERROR') {
                $error = (string) ($xml->Errors->Error ?? 'Unknown error');
                \Illuminate\Support\Facades\Log::error("Namecheap API Error for {$domain}: {$error}");
                return [];
            }

            $hosts = [];
            $result = $xml->CommandResponse->DomainDNSGetHostsResult;
            
            if (isset($result->host)) {
                foreach ($result->host as $host) {
                    $hosts[] = [
                        'Host' => (string) $host['Name'],
                        'Type' => (string) $host['Type'],
                        'Address' => (string) $host['Address'],
                        'TTL' => (string) $host['TTL'],
                    ];
                }
            } else {
                \Illuminate\Support\Facades\Log::warning("Namecheap API: No hosts found in response for {$domain}. XML: " . $response->body());
            }
            
            return $hosts;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Namecheap Service getHosts Exception: " . $e->getMessage());
            return [];
        }
    }
    public function addHost(string $domain, string $host, string $type, string $address, int $ttl = 60): bool
    {
        if (config('services.namecheap.mock')) {
            return true;
        }

        try {
            // 1. Lấy danh sách hosts hiện tại
            $existingHosts = $this->getHosts($domain);
            
            // 2. Chuẩn bị dữ liệu để gửi setHosts (phải gửi lại toàn bộ list cũ + mới)
            $parts = explode('.', $domain);
            $tld = array_pop($parts);
            $sld = implode('.', $parts);

            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->apiUser,
                'Command' => 'namecheap.domains.dns.setHosts',
                'ClientIp' => $this->clientIp,
                'SLD' => $sld,
                'TLD' => $tld,
            ];

            // Thêm các host cũ vào params
            foreach ($existingHosts as $index => $h) {
                $i = $index + 1;
                $params["HostName{$i}"] = $h['Host'];
                $params["RecordType{$i}"] = $h['Type'];
                $params["Address{$i}"] = $h['Address'];
                $params["TTL{$i}"] = $h['TTL'];
            }

            // Thêm host mới vào cuối danh sách
            $nextIndex = count($existingHosts) + 1;
            $params["HostName{$nextIndex}"] = $host;
            $params["RecordType{$nextIndex}"] = $type;
            $params["Address{$nextIndex}"] = $address;
            $params["TTL{$nextIndex}"] = $ttl;

            $response = \Illuminate\Support\Facades\Http::asForm()->post($this->baseUrl, $params);
            $xml = simplexml_load_string($response->body());

            if ($xml && (string) $xml['Status'] === 'OK') {
                return true;
            }

            \Illuminate\Support\Facades\Log::error("Namecheap API addHost Error for {$domain}: " . $response->body());
            return false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Namecheap Service addHost Exception: " . $e->getMessage());
            return false;
        }
    }
}
