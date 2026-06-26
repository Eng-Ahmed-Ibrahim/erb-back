<?php

namespace App\Service\NetworkTracking\Console\Jobs;

use App\Service\NetworkTracking\Repositories\NetworkDeviceRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanArpTableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected NetworkDeviceRepository $repository;

    /**
     * Create a new job instance.
     */
    // public function __construct(NetworkDeviceRepository $repository)
    // {
    //     // parent::__construct();
    //     $this->repository = $repository;
    // }

    public function handle(NetworkDeviceRepository $repository)
    {
        echo '🔍 Scanning ARP table...';
        $output = shell_exec('arp -a');

        if (!$output) {
            echo '❌ ARP scan failed or returned empty.';
            return 1;
        }

        $lines = explode("\n", $output);
        $count = 0;

        foreach ($lines as $line) {
            // Match both IP and MAC
            if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)\s+([a-f0-9:-]{11,17})\s+(\w+)/i', $line, $matches)) {
                $ip = trim($matches[1]);
                $mac = strtolower(str_replace('-', ':', trim($matches[2])));
                $type = strtolower(trim($matches[3]));

                $repository->upsertByMacOrCreate($mac, [
                    'mac_address' => $mac,
                    'ip_address' => $ip,
                ]);

                // echo "✅ Synced [$type] — $mac -> $ip";
                $count++;
            }
        }

        echo "✅ ARP scan complete. Total devices processed: $count";
        return 0;
    }
}
