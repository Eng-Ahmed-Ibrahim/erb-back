<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\SearchTaintedInvoiceRequest;
use App\Http\Requests\Invoices\StoreTaintedInvoicesRequest;
use App\Http\Requests\Invoices\UpdateTaintedInvoicesRequest;
use App\Repositories\Invoice\InvoiceRepository;
use App\Service\Factory\InvoiceFactory;
use App\Transformers\Invoices\TaintedInvoiceTransformer;
use Illuminate\Http\Response;

class TaintedInvoiceController extends Controller
{
    public function __construct(private InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function index(SearchTaintedInvoiceRequest $request)
    {
        $data = $request->validated();
        $data['type'] = 'tainted';
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }
        $invoices = $this->invoiceRepository->getInterceptedByAttributes($data, 'created_at', 'desc');
        if (isset($data['date']) && $data['date']) {
            $invoices = $invoices->whereBetween('invoice_date', [$date['from'], $date['to']]);
        }

        return responder()->success($this->invoiceRepository->paginate($invoices), TaintedInvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreTaintedInvoicesRequest $request)
    {
        $data = $request->validated();
        $invoice = InvoiceFactory::invoiceBasedOnType('tainted')->createInvoice($data);
        if (! $invoice['status']) {
            return responder()->error('error', $invoice['message'])->respond(Response::HTTP_BAD_REQUEST);
        }

        return responder()->success($invoice, TaintedInvoiceTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($taintedInvoice_id)
    {
        $taintedInvoice = $this->invoiceRepository->find($taintedInvoice_id);

        return responder()->success($taintedInvoice, TaintedInvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateTaintedInvoicesRequest $request, $taintedInvoice_id)
    {
        $data = $request->validated();
        $invoice = $this->invoiceRepository->find($taintedInvoice_id);
        if ($invoice->status == 'approved') {
            return responder()->error('can\'t_update', 'لا يمكن تعديل هذه الفاتورة');
        }
        InvoiceFactory::invoiceBasedOnType($invoice['type'])->updateInvoiceQuantity($invoice, $data);

        return responder()->success(['message' => 'تم تعديل الفاتورة بنجاح'])->respond(Response::HTTP_OK);
    }
}
