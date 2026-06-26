<?php

namespace App\Jobs;

use App\Models\Department;
use App\Models\Recipe;
use App\Service\Balance\DepartmentBalance;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FillDepartmentsBalances implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $departmentBalance;

    protected $startDate;

    protected $now;

    /**
     * Create a new job instance.
     *
     * @param  DepartmentBalance  $departmentBalance
     */
    public function __construct()
    {
        $this->departmentBalance = new DepartmentBalance;
        $this->startDate = Carbon::parse('2024-04-01');
        $this->now = now()->format('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $departments = Department::all();
        $recipes = Recipe::all();

        $this->initializeDepartmentsRecipes($departments, $recipes);
        $this->processDepartmentBalances($departments);
    }

    protected function initializeDepartmentsRecipes($departments, $recipes): void
    {
        foreach ($departments as $department) {
            $department->recipes()->detach();
            foreach ($recipes as $recipe) {
                $department->recipes()->attach($recipe->id, [
                    'recipe_id' => $recipe->id,
                    'department_id' => $department->id,
                    'quantity' => 0,
                    'price' => 0,
                ]);

            }
        }
    }

    protected function processDepartmentBalances($departments): void
    {
        $day = $this->startDate->copy();

        while ($day->format('Y-m-d') < $this->now) {
            foreach ($departments as $department) {
                $from = $day->format('Y-m-d');
                $to = $day->copy()->addDay();

                $this->calculateStore($department, $from, $to);
            }
            $departments->each(function ($department) {
                $balance = $department->balances()->create([
                    'date' => now(),
                ]);
                $departmentRecipes = $department->recipes()->get();

                $departmentRecipes->each(function ($recipe) use ($balance, $department) {
                    $balance->recipeBalances()->create([
                        'department_id' => $department->id,
                        'date' => $balance->date,
                        'quantity' => $recipe->pivot->quantity,
                        'recipe_id' => $recipe->id,
                        'total_price' => $recipe->pivot->price,
                    ]);
                });
            });
            $day->addDay();
        }
    }

    private function calculateStore($department, $from, $to)
    {
        $fromInvoices = $department->fromInvoices()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->get();

        $toInvoices = $department->toInvoices()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->get();

        $this->calculateInvoices($fromInvoices, $department);
        $this->calculateInvoices($toInvoices, $department);

    }

    private function calculateInvoices($invoices, $department)
    {
        foreach ($invoices as $invoice) {
            $recipes = $invoice->recipes;
            foreach ($recipes as $recipe) {
                $total = 0;
                $recipeStore = $department->recipes()->where('recipes.id', $recipe->id)->first()->pivot;
                $recipeQuantity = $recipeStore->quantity;
                switch ($invoice->type) {
                    case 'out_going':
                        if ($department->id == $invoice->from) {
                            $total = $recipeQuantity - $recipe->pivot->quantity;
                        } elseif ($invoice->to == $department->id) {
                            $total = $recipeQuantity + $recipe->pivot->quantity;
                        }
                        break;
                    case 'in_coming':
                        $total = $recipeQuantity + $invoice->pivot->quantity;
                        break;
                    case 'returned':
                        if ($invoice->to == $department->id) {
                            $total = $recipeQuantity + $invoice->pivot->quantity;
                        } elseif ($invoice->from == $department->id) {
                            $total = $recipeQuantity - $invoice->pivot->quantity;
                        }
                        break;
                    case 'tainted':
                        $total = $recipeQuantity - $invoice->pivot->quantity;
                        break;
                }
                $recipeStore->quantity = $total;
                $recipeStore->save();
            }
        }
    }
}
