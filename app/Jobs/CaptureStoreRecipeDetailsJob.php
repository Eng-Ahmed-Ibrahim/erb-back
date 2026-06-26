<?php

namespace App\Jobs;

use App\Models\DepartmentStore;
use App\Models\InventoryArchive;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CaptureStoreRecipeDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public function handle(): void
    {
        try {
            Log::info('Starting CaptureStoreRecipeDetailsJob');

            DepartmentStore::chunk(1000, function ($recipes) {
                $data = [];

                foreach ($recipes as $recipe) {
                    $data[] = [
                        'id' => Str::uuid(),
                        'recipe_id' => $recipe->recipe_id,
                        'department_id' => $recipe->department_id,
                        'quantity' => $recipe->quantity,
                        'price' => $recipe->price,
                        'captured_at' => Carbon::now(),
                    ];
                }

                if (! empty($data)) {
                    DB::transaction(function () use ($data) {
                        InventoryArchive::insert($data);
                    });
                }
            });

            Log::info('Completed CaptureStoreRecipeDetailsJob');

        } catch (\Exception $e) {
            Log::error('Error in CaptureStoreRecipeDetailsJob: '.$e->getMessage());
            $this->fail($e);
        }
    }
}
