<?php

namespace App\Service\NetworkTracking\Console\Commands;

use Illuminate\Console\Command;
use App\Service\NetworkTracking\Console\Jobs\ScanArpTableJob;
class ScanArpTable extends Command
{
    protected $signature = 'network:scan-arp';
    protected $description = 'Scans the ARP table and upserts IP-to-MAC mappings into DB';

    public function handle()
    {
        ScanArpTableJob::dispatch()->onQueue('');
    }
}
