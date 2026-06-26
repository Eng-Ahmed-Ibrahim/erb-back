<?php

namespace App\Repositories\Invoice;

use App\Repositories\BaseRepository;

interface InvoiceRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function reviewInvoice($model);

    public function moveInvoiceToDepartment($data);
}
