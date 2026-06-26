<?php

namespace App\Console\Commands;

use App\Jobs\FillDepartmentsBalances;
use Illuminate\Console\Command;

class TestDepartmentBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-department-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        (new FillDepartmentsBalances)->handle();
    }
}
