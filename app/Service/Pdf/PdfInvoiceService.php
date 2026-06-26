<?php

namespace App\Service\Pdf;

use App\Models\Invoice;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class PdfCreateInvoiceService
{
    public function generateInvoicePdf($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        $invoice->recipes;

        return view('pdf.invoices.invoice', compact('invoice'));
        // $pdf = PDF::loadView('pdf.invoices.invoice', $data);
        // return $pdf->stream('document.pdf');
    }
}
