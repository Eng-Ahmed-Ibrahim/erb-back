<?php

namespace App\Console\Commands;

use App\Models\Department;
use Illuminate\Console\Command;

class StoreDeparemtnsRecipesBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store-deparemtns-recipes-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command to create report for each department with its recipes balances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $departments = Department::select('id')->get();
        $departments->each(function ($department) {
            $balance = $department->balances()->create([
                'date' => now(),
            ]);
            $recipes = $department->recipes;
            $recipes->each(function ($recipe) use ($balance, $department) {
                $balance->recipeBalances()->create([
                    'department_id' => $department->id,
                    'date' => $balance->date,
                    'quantity' => $recipe->pivot->quantity,
                    'recipe_id' => $recipe->id,
                    'total_price' => $recipe->total_price,
                ]);
            });
        });
    }
}
