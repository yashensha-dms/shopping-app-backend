<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Helpers\Helpers;
use App\Models\Attachment;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AttachmentRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name' => 'like',
        'file_name' => 'like',
        'collection_name' => 'like',
    ];

    public function boot()
    {
        try {

            $this->pushCriteria(app(RequestCriteria::class));

        } catch (ExceptionHandler $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    function model()
    {
        return Attachment::class;
    }

    public function show($id)
    {
        try {

            return $this->model->findOrFail($id);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function store($request)
    {
        $user = Auth::guard('api')->user() ?? Helpers::getAdmin();
        $createdAttachments = [];

        if ($request->url) {
            $url = $request->url;
            $path = parse_url($url, PHP_URL_PATH);
            $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpeg';
            $originalName = pathinfo($path, PATHINFO_FILENAME) ?: 'external_image';
            $slugName = Str::slug($originalName);
            $fileName = $slugName . '.' . $extension;

            $attachment = $this->model->create([
                'uuid' => (string) Str::uuid(),
                'name' => $slugName,
                'file_name' => $fileName,
                'disk' => 'external',
                'collection_name' => 'attachment',
                'mime_type' => 'image/' . $extension,
                'size' => 0,
                'custom_properties' => ['external_url' => $url],
                'model_type' => null,
                'model_id' => null,
                'order_column' => ($this->model->max('order_column') ?? 0) + 1,
                'created_by_id' => $user->id,
            ]);
            $createdAttachments[] = $attachment;
        }

        $files = [];
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $files[] = $file;
            }
        }

        if ($user) {
            foreach ($files as $file) {
                $createdAttachments[] = $user->addMedia($file)->toMediaCollection('attachment');
            }
        } else {
             throw new Exception('User not found for media upload', 404);
        }

        return $createdAttachments;
    }

    public function destroy($id)
    {
        try {

            $attachment = $this->model->findOrFail($id);
            return Helpers::deleteImage($attachment);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function deleteAll($ids)
    {
        try {

            return $this->model->whereIn('id', $ids)->delete();

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }
}
