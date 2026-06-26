<?php

namespace App\Service\Balance;

use App\Models\Department;
use App\Models\Recipe;

class DepartmentBalance
{
    public function getDepartmentBalances($department_id, $fromDate, $toDate)
    {
        $department = Department::findOrFail($department_id);
        $departmentBalance = $department->balances()->where('date', $fromDate)->first();
        if (! $departmentBalance) {
            foreach (Recipe::all() as $recipe) {
                $recipes[] = [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'image' => $recipe->image,
                    'total_out_going' => 0,
                    'total_incoming' => 0,
                    'total_returned' => 0,
                    'total_tainted' => 0,
                    'total' => 0,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ];
            }
        } else {
            $startBalanceRecipes = $departmentBalance->recipeBalances()->where('date', $fromDate)->get();
            $recipes = [];
            foreach ($startBalanceRecipes as $recipe) {
                $recipeInvoices = $recipe->invoices()->where('created_at', '>=', $fromDate)->where('created_at', '<=', $toDate)->get();
                $invoicesToals = $this->calculateInvoices($recipeInvoices, $department);
                $total = $recipe->pivot()->quantity + $invoicesToals['totalIncoming'] - $invoicesToals['totalOutGoing'] - $invoicesToals['totalReturned'] - $invoicesToals['totalTainted'];

                $recipes[] = [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'image' => $recipe->image,
                    'total_out_going' => $invoicesToals['totalOutGoing'],
                    'total_incoming' => $invoicesToals['totalIncoming'],
                    'total_returned' => $invoicesToals['totalReturned'],
                    'total_tainted' => $invoicesToals['totalTainted'],
                    'total' => $total,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ];
            }
        }

        return $recipes;
    }

    private function calculateInvoices($invoices, $department)
    {
        $totalOutGoing = 0;
        $totalIncoming = 0;
        $totalReturned = 0;
        $totalTainted = 0;
        foreach ($invoices as $invoice) {
            switch ($invoice->type) {
                case 'outgoing':
                    if ($invoice->from == $department->id) {
                        $totalOutGoing -= $invoice->pivot->quantity;
                    } elseif ($invoice->to == $department->id) {
                        $totalOutGoing += $invoice->pivot->quantity;
                    }
                    break;
                case 'in_coming':
                    $totalIncoming += $invoice->pivot->quantity;
                    break;
                case 'returned':
                    if ($invoice->to == $department->id) {
                        $totalReturned += $invoice->pivot->quantity;
                    } elseif ($invoice->from == $department->id) {
                        $totalReturned += $invoice->pivot->quantity;
                    }
                    break;
                case 'tainted':
                    $totalTainted += $invoice->pivot->quantity;
                    break;
            }
        }

        return [
            'totalOutGoing' => $totalOutGoing,
            'totalIncoming' => $totalIncoming,
            'totalReturned' => $totalReturned,
            'totalTainted' => $totalTainted,
        ];
    }
}
