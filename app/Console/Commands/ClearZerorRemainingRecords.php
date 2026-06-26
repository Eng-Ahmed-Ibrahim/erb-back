<?php

namespace App\Console\Commands;

use App\Models\RecipeQuantity;
use Illuminate\Console\Command;

class ClearZerorRemainingRecords extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-zeror-remaining-records';

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
        RecipeQuantity::where('remaining', 0)->delete();
        $this->info('Zero Remaining records deleted successfully.');
    }
}
