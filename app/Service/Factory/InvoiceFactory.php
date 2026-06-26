<?php

namespace App\Service\Factory;

use App\Service\Factory\Invoices\InComingInvoice;
use App\Service\Factory\Invoices\InventoryAdjustmentInvoice;
use App\Service\Factory\Invoices\OutGoningInvoice;
use App\Service\Factory\Invoices\ReturnedInvoice;
use App\Service\Factory\Invoices\TaintedInvoice;
use App\Service\Factory\Invoices\TransfareInvoice;

class InvoiceFactory
{
    public static function invoiceBasedOnType($data): InvoiceInterface
    {
        switch ($data) {
            case 'out_going':
                return new OutGoningInvoice;
                break;
            case 'in_coming':
                return new InComingInvoice;
                break;
            case 'returned':
                return new ReturnedInvoice;
                break;
            case 'tainted':
                return new TaintedInvoice;
                break;
            case 'transfare':
                return new TransfareInvoice;
                break;
            case 'inventory_adjustment':
                return new InventoryAdjustmentInvoice;
                break;
        }
    }
}
