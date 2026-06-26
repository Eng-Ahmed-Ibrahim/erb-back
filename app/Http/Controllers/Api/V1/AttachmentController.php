<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Attachment\AttachmentRepository;
use App\Transformers\Attachment\AbstractAttachmentTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AttachmentController extends Controller
{
    public function __construct(
        private AttachmentRepository $attachmentRepository
    ) {
        $this->attachmentRepository = $attachmentRepository;
    }

    public function index(Request $request)
    {
        $bookingId = $request->input('booking_id');
        $type = $request->input('type');

        if ($bookingId) {
            $data = $this->attachmentRepository->getByBooking($bookingId);
        } elseif ($type) {
            $data = $this->attachmentRepository->getByType($type);
        } else {
            $data = $this->attachmentRepository->all('created_at', 'desc');
        }

        $data = $this->attachmentRepository->paginate($data);

        return responder()->success($data, AbstractAttachmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $data = $this->attachmentRepository->find($id);

        return responder()->success($data, AbstractAttachmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'type' => 'required|in:document,permit,signature',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $bookingId = $request->input('booking_id');
        $type = $request->input('type');

        $attachment = $this->attachmentRepository->uploadFile($file, $bookingId, $type);

        return responder()->success($attachment, AbstractAttachmentTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function destroy(string $id)
    {
        $model = $this->attachmentRepository->find($id);
        $this->attachmentRepository->adminDelete($model);

        return responder()->success([])->respond(Response::HTTP_OK);
    }

    public function getByBooking(string $bookingId)
    {
        $attachments = $this->attachmentRepository->getByBooking($bookingId);

        return responder()->success($attachments, AbstractAttachmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getByType(string $type)
    {
        $attachments = $this->attachmentRepository->getByType($type);

        return responder()->success($attachments, AbstractAttachmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function uploadForBooking(Request $request, string $bookingId)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max
            'type' => 'nullable|in:document,permit,signature',
        ]);

        $file = $request->file('file');
        $type = $request->input('type', 'document');

        $attachment = $this->attachmentRepository->uploadFile($file, $bookingId, $type);

        return responder()->success($attachment, AbstractAttachmentTransformer::class)->respond(Response::HTTP_CREATED);
    }
}