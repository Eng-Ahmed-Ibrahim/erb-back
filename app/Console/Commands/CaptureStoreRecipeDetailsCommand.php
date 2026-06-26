<?php

namespace App\Console\Commands;

use App\Jobs\CaptureStoreRecipeDetailsJob;
use Illuminate\Console\Command;

class CaptureStoreRecipeDetailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:capture-store-recipe-details';

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
        CaptureStoreRecipeDetailsJob::dispatch();
    }
}
