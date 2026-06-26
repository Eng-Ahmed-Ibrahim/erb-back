<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\requests\Transaction\SearchTransactionRequest;
use App\Repositories\Transaction\TransactionRepository;
use App\Transformers\Transaction\AbstractTransactionTransformer;
use App\Transformers\Transaction\TransactionTransformer;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    public function __construct(private TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function index(SearchTransactionRequest $request)
    {

        if (count($request->validated()) > 0) {
            $categories = $this->transactionRepository->filter($request->validated(), 'created_at', 'desc')->get();

            return responder()->success($this->transactionRepository->paginate($categories), AbstractTransactionTransformer::class)->respond(Response::HTTP_OK);
        }
        $transactions = $this->transactionRepository->allPaginated('created_at', 'desc');

        return responder()->success($transactions, AbstractTransactionTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show($transaction_id)
    {
        $transactions = $this->transactionRepository->find($transaction_id);

        return responder()->success($transactions, TransactionTransformer::class)->respond(Response::HTTP_OK);
    }
}
