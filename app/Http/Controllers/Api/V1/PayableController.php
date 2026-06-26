<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\requests\Payable\SearchPayableRequest;
use App\Http\requests\Payable\StorePayableRequest;
use App\Http\Requests\Payable\UpdatePayableRequest;
use App\Repositories\Invoice\InvoiceRepository;
use App\Repositories\Payable\PayableRepository;
use App\Transformers\Payable\AbstractPayableTransformer;
use Illuminate\Http\Response;

class PayableController extends Controller
{
    protected $transactionService;

    public function __construct(private PayableRepository $payableRepository, private InvoiceRepository $invoiceRepository)
    {
        $this->payableRepository = $payableRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function index(SearchPayableRequest $request)
    {
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }
        if (count($data) > 0) {
            $payables = $this->payableRepository->getInterceptedByAttributes($data, 'created_at', 'desc');
            if (isset($date['from']) && isset($date['to'])) {
                $payables = $payables->whereBetween('created_at', [$date['from'], $date['to']]);
            }

            return responder()->success($this->payableRepository->paginate($payables), AbstractPayableTransformer::class)->respond(Response::HTTP_OK);
        }
        $payables = $this->payableRepository->all();
        $payables = $this->payableRepository->paginate($payables);
        if (isset($date['from']) && isset($date['to'])) {
            $payables = $payables->whereBetween('created_at', [$date['from'], $date['to']]);
        }

        return responder()->success($payables, AbstractPayableTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StorePayableRequest $request)
    {
        if ($request->type == 'invoices') {
            $invoice = $this->invoiceRepository->find($request->invoice_id);
            if ($invoice->is_paid) {
                return responder()->error("can't_create", 'تم دفع هذه الفاتورة من قبل بالفعل عليك بمراحعة المدفوعات السابقة')->respond(Response::HTTP_OK);
            }
        }
        $payable = $this->payableRepository->adminCreate($request->validated());

        return responder()->success($payable, AbstractPayableTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($payable_id)
    {
        $payable = $this->payableRepository->find($payable_id);

        return responder()->success($payable, AbstractPayableTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdatePayableRequest $request, $payable_id)
    {
        $payables = $this->payableRepository->find($payable_id);
        $this->payableRepository->adminUpdate($payables, $request->validated());

        return responder()->success(['message' => 'تم تعديل العنصر بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($payable_id)
    {
        $payable = $this->payableRepository->find($payable_id);
        $this->payableRepository->delete($payable);

        return responder()->success(['message' => 'تم حذف العنصر بنجاح'])->respond(Response::HTTP_OK);
    }
}
