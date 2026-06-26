<?php

namespace App\Console\Commands;

use App\Jobs\ChangeCashierDepartmentJob;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ChangeCashierDepartmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-cashier-department';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'change the cashier department ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shifts = Shift::where('start', '<=', Carbon::now()->addHours(6))
            ->where('end', '>=', Carbon::now())
            ->where('is_closed', false)
            ->get();

        foreach ($shifts as $shift) {
            ChangeCashierDepartmentJob::dispatch($shift)->onQueue('shifts');
        }
        Log::info('Job Finished to chnage the shift for cashiers');
    }
}
