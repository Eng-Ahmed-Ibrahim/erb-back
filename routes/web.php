<?php

use App\Models\Department;
use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

// Route::get('/', function () {
//     $invoice = Invoice::find(46);
//     $invoice->recipes;
//     return view('pdf.invoices.invoice', compact('invoice'));
// });

// Route::get('/2', function () {
//     $invoice = Invoice::find(46);
//     $invoice->recipes;
//     // return view('pdf.invoices.printed_invoice', compact('invoice'));
//     $pdf = PDF::loadView('pdf.invoices.printed_invoice', compact('invoice'));
//     return $pdf->stream('document.pdf');
// });

Route::get('departments', function () {
    $departments = Department::where('id', '!=', '01j76p6rt8stn4xx6taks46j7j')
        ->where('type', 'both')
        ->get();
    foreach ($departments as $department) {
        $department->delete();
    }
});

Route::get('/delete-departments', function () {
    $suppliers = Supplier::all();
    $arr = ['contracted', 'local'];
    $rand = rand(0, 1);
    foreach ($suppliers as $supplier) {
        $rand = rand(0, 1);
        $supplier->type = $arr[$rand];
        $supplier->save();
    }
});
Route::get('/',function(){
    return Cache::get('waiters');
});
