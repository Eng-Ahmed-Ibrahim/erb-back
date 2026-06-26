<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WarehouseSections;
use Illuminate\Http\Response;

class WarehouseSectionsController extends Controller
{
    public function index()
    {
        $sections = WarehouseSections::all();

        return responder()->success($sections)->respond(Response::HTTP_OK);
    }
}
