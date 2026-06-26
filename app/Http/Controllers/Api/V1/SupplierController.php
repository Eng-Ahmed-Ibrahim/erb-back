<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\SearchInvoicesRequest;
use App\Http\Requests\Supplier\SearchSupplierRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Repositories\Invoice\InvoiceRepository;
use App\Repositories\Supplier\SupplierRepository;
use App\Transformers\Invoices\InvoiceTransformer;
use App\Transformers\Supplier\ShowSupplierTransformer;
use App\Transformers\Supplier\SupplierTransformer;
use Illuminate\Http\Response;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class SupplierController extends Controller
{
    public function __construct(
        private SupplierRepository $supplierRepository,
        private InvoiceRepository $invoiceRepository
    ) {
        $this->supplierRepository = $supplierRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function index(SearchSupplierRequest $request)
    {
        $data = $request->validated();
        $addationalData = [];

        if (isset($data['from_date'])) {
            $addationalData['from_date'] = $data['from_date'];
            unset($data['from_date']);
        }

        if (isset($data['to_date'])) {
            $addationalData['to_date'] = $data['to_date'];
            unset($data['to_date']);
        }

        if (isset($data['warehouse_section_id'])) {
            $addationalData['warehouse_section_id'] = $data['warehouse_section_id'];
            unset($data['warehouse_section_id']);
        }

        if (count($data) > 0) {
            $data = array_filter($data, function ($item) {
                return $item !== null;
            });
            $suppliers = $this->supplierRepository->getInterceptedByAttributes($data, 'created_at', 'desc');

            return responder()->success(ShowSupplierTransformer::transform($suppliers, $addationalData))->respond(Response::HTTP_OK);
        }

        // $suppliers = $this->supplierRepository->allPaginated('created_at', 'desc');
        $suppliers = $this
            ->supplierRepository
            ->orderBy('created_at', 'desc')
            ->where('blocked_at', null)
            ->when(isset(auth()->user()->roles()->first()->id) && auth()->user()->roles()->first()->id == '9c10deda-c41a-4c2c-9e5e-eb48322e038c', fn ($q) => $q
                ->whereNotIn('id', ['01jbacxsqzcp6e23vryy75pdb5', '01jbcz6ngedke0518zv6m1h9rq', '01jd7s43xfkbtp8vj9z35ksh7d', '01jdm2wc2eg7ssjv825y4xa9nm', '01jdhcftes75s5ctpygazx3knn']))
            ->get();
        // $total =  $supplier->sum()

        return responder()->success(ShowSupplierTransformer::transform($suppliers, $addationalData))->respond(Response::HTTP_OK);
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = $this->supplierRepository->adminCreate($request->validated());

        return responder()->success($supplier, SupplierTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($supplier_id)
    {
        $supplier = $this->supplierRepository->find($supplier_id);

        return responder()->success($supplier, SupplierTransformer::class)->respond(Response::HTTP_OK);
    }

    public function showInvoices($supplier_id, SearchInvoicesRequest $request)
    {
        $supplier = $this->supplierRepository->find($supplier_id);
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }

        if (count($data) > 0) {
            $invoices = $this->invoiceRepository->getIntercepted($data, ['supplier_id' => $supplier_id], 'created_at', 'desc');
            if (! empty($date) && ($date['from'] && $date['to'])) {
                $invoices = $invoices->whereBetween('invoice_date', [$date['from'], $date['to']]);
            }

            return responder()->success($this->invoiceRepository->paginate($invoices), InvoiceTransformer::class)->respond(Response::HTTP_OK);
        }
        $invoices = $this->invoiceRepository->getInterceptedByAttributes2(['supplier_id' => $supplier->id])
            ->whereBetween('created_at', [$date['from'], $date['to']])
            ->orderBy('created_at', 'desc')->get();

        $totalInvoicePrice = $invoices->sum('invoice_price');
        $paginatedInvoices = $this->invoiceRepository->paginate($invoices);

        $fractal = new Manager;

        $resource = new Collection($paginatedInvoices->items(), new InvoiceTransformer);
        $transformedInvoices = $fractal->createData($resource)->toArray();

        return responder()->success([
            'data' => $transformedInvoices['data'],
            'pagination' => [
                'current_page' => $paginatedInvoices->currentPage(),
                'last_page' => $paginatedInvoices->lastPage(),
                'per_page' => $paginatedInvoices->perPage(),
                'total' => $paginatedInvoices->total(),
            ],
            'total' => $totalInvoicePrice,
        ])->respond(Response::HTTP_OK);

        return responder()->success($this->supplierRepository->paginate($invoices), InvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateSupplierRequest $request, $supplier_id)
    {
        $supplier = $this->supplierRepository->find($supplier_id);
        $this->supplierRepository->adminUpdate($supplier, $request->validated());

        return responder()->success(['message' => 'تم تعديل المورد بنجاح'])->respond(Response::HTTP_OK);
    }

    public function changeSupplierBlockStatus($id)
    {
        $supplier = $this->supplierRepository->find($supplier_id);
    }

    public function delete($supplier_id)
    {
        $supplier = $this->supplierRepository->find($supplier_id);
        if ($supplier->invoices->count() > 0) {
            return responder()->error('can\t_delete', 'هذا المورد مرتبط بفواتير')->respond(Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->supplierRepository->adminDelete($supplier);

        return responder()->success(['message' => 'تم حذف المورد بنجاح'])->respond(Response::HTTP_OK);
    }
}
