<?php

namespace App\Repositories\Payable\Eloquent;

use App\Models\Payable;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Payable\PayableRepository;
use App\Repositories\Transaction\TransactionRepository;
use Illuminate\Support\Facades\Storage;

class EloquentPaybleRepository extends EloquentBaseRepository implements PayableRepository
{
    private $transactionRepository;

    public function __construct()
    {
        parent::__construct(new Payable);
        $this->transactionRepository = app(TransactionRepository::class);
    }

    public function adminCreate($data)
    {
        if (isset($data['image']) && $data['image']) {
            $data['image'] = $this->saveImage($data['image'], 'invoices_images');
        }

        $payable = $this->create($data);
        $this->transactionRepository->createPayableTransaction($payable);
        if ($payable->invoice) {
            $invoice = $payable->invoice;
            $invoice->is_paid = true;
            $invoice->save();
        }

        return $payable;
    }

    public function adminUpdate($model, $data)
    {

        if (isset($data['image'])) {
            if ($data['image']) {
                Storage::disk('public')->delete($model->image);
                $data['image'] = $this->saveImage($data['image'], 'recipes_images');
            } else {
                unset($data['image']);
            }
        }

        return $this->update($model, $data);
        // update transaction
    }

    public function adminDelete($model)
    {

        return $this->delete($model);
    }
}
