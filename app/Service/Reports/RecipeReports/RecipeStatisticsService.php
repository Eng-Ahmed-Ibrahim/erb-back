<?php

namespace App\Service\Reports\RecipeReports;

class RecipeStatisticsService
{
    public function recipeInvoiceReport($department, $recipe, $date, $type = null)
    {
        $invoices = $recipe
            ->invoices()
            ->where(function ($query) {
                $query
                    ->where('supplier_id', '!=', '01jbacxsqzcp6e23vryy75pdb5')
                    ->where('supplier_id', '!=', '01jbcz6ngedke0518zv6m1h9rq')
                    ->where('supplier_id', '!=', '01jd7s43xfkbtp8vj9z35ksh7d')
                    ->orWhereNull('supplier_id');
            })
            // ->where(function ($query) use ($department) {
            //     $query
            //         ->where('from', $department->id)
            //         ->orWhere('to', $department->id);
            // })
            ->where('invoice_date', '>=', $date['from'])
            ->where('invoice_date', '<=', $date['to'])
            ->when(isset($type), fn ($q) => $q->where('type', $type))
            ->get();

        $totalQuantity = 0;
        $totalPrice = 0;
        $report = [];

        foreach ($invoices as $invoice) {

            switch ($invoice->type) {
                case 'in_coming':
                    $report[] = $this->formateInvoice($invoice);
                    $totalQuantity += $invoice->pivot->quantity;
                    $totalPrice += $invoice->pivot->total_price;
                    break;
                case 'out_going':
                    $report[] = $this->formateInvoice($invoice);
                    $totalQuantity -= $invoice->pivot->quantity;
                    $totalPrice -= $invoice->pivot->total_price;
                    break;
                case 'returned':
                    $report[] = $this->formateInvoice($invoice);
                    $totalQuantity += $invoice->pivot->quantity;
                    $totalPrice += $invoice->pivot->total_price;
                    break;
            }
        }
        $report['totals']['totalQuantity'] = $totalQuantity;
        $report['totals']['total'] = $totalPrice;

        return $report;
    }

    private function calcoulateOutGoingTotals($invoice, $department, $totalQuantity, $totalPrice)
    {
        if ($invoice->from == $department->id) {
            $totalQuantity -= $invoice->pivot->quantity;
            $totalPrice -= ($invoice->pivot->price * $invoice->pivot->quantity);

            return [
                'totalQuantity' => $totalQuantity,
                'totalPrice' => $totalPrice,
            ];
        } elseif ($invoice->to == $department->id) {
            $totalQuantity += $invoice->pivot->quantity;
            $totalPrice += ($invoice->pivot->price * $invoice->pivot->quantity);

            return [
                'totalQuantity' => $totalQuantity,
                'totalPrice' => $totalPrice,
            ];
        }
    }

    private function calculateReturnedTotals($invoice, $department, $totalQuantity, $totalPrice)
    {
        if ($invoice->from == $department->id) {
            $totalQuantity += $invoice->pivot->quantity;
            $totalPrice += ($invoice->pivot->price * $invoice->pivot->quantity);

            return [
                'totalQuantity' => $totalQuantity,
                'totalPrice' => $totalPrice,
            ];
        } elseif ($invoice->to == $department->id) {
            $totalQuantity -= $invoice->pivot->quantity;
            $totalPrice -= ($invoice->pivot->price * $invoice->pivot->quantity);

            return [
                'totalQuantity' => $totalQuantity,
                'totalPrice' => $totalPrice,
            ];
        }
    }

    private function formateInvoice($invoice)
    {
        return [
            'type' => $invoice->type,
            'id' => $invoice->id,
            'code' => $invoice->code,
            'date' => $invoice->invoice_date,
            'quantity' => $invoice->pivot->quantity,
            'price' => $invoice->pivot->price,
            'total_price' => $invoice->pivot->price * $invoice->pivot->quantity,
            'supplier' => [
                'id' => $invoice->supplier?->id,
                'name' => $invoice->supplier?->name,
            ],
            'from' => [
                'id' => $invoice->fromDepartment?->id,
                'name' => $invoice->fromDepartment?->name,
            ],
            'to' => [
                'id' => $invoice->toDepartment?->id,
                'name' => $invoice->toDepartment?->name,
            ],
        ];
    }
}
