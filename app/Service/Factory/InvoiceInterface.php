<?php

namespace App\Service\Factory;

interface InvoiceInterface
{
    public function createInvoice($data);

    public function updateInvoicePrices($invoice, $data);

    public function updateInvoiceQuantity($invoice, $data);
}
