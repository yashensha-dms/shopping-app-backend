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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\Pool;

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
                'name' => $request->name ?? $slugName,
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

        try {
            $files = [];
            if ($request->hasFile('file')) {
                $files[] = $request->file('file');
            }

            if ($request->hasFile('attachments')) {
                $attachments = $request->file('attachments');
                if (is_array($attachments)) {
                    foreach ($attachments as $file) {
                        $files[] = $file;
                    }
                } else {
                    $files[] = $attachments;
                }
            }

            // Ensure we have a user or admin to 'own' the media
            $owner = Auth::guard('api')->user() ?? Helpers::getAdmin();
            if (!$owner) {
                 throw new Exception('No authorized user found to process upload', 401);
            }

            foreach ($files as $file) {
                // We use the owner (user/admin) to add the media to the collection
                $createdAttachments[] = $owner->addMedia($file)
                    ->usingName($request->name ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    ->toMediaCollection('attachment');
            }

            return $createdAttachments;

        } catch (Exception $e) {
            // Log the error and throw it so the frontend can see it
            \Log::error('Media Upload Error: ' . $e->getMessage());
            throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
        }
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

    public function syncCloudinary($request)
    {
        @set_time_limit(120);
        try {
            $limit = (int) ($request->limit ?: 15);
            $deleteDead = $request->has('delete_dead') ? (bool) $request->delete_dead : true;

            $baseQuery = $this->model->where(function ($q) {
                $q->where('disk', 'external')
                  ->orWhere('custom_properties', 'like', '%cloudinary%')
                  ->orWhere('file_name', 'like', '%cloudinary%')
                  ->orWhere('name', 'like', '%cloudinary%');
            });

            if ($request->ids && is_array($request->ids) && count($request->ids) > 0) {
                $baseQuery->whereIn('id', $request->ids);
            }

            $totalRemaining = (clone $baseQuery)->count();
            $attachments = (clone $baseQuery)->take($limit)->get();

            if ($attachments->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No Cloudinary media found to sync.',
                    'processed_count' => 0,
                    'synced_count' => 0,
                    'deleted_dead_count' => 0,
                    'failed_count' => 0,
                    'remaining_count' => 0,
                    'has_more' => false,
                    'log' => [],
                ]);
            }

            $syncedCount = 0;
            $deletedDeadCount = 0;
            $failedCount = 0;
            $log = [];

            // 1. Prepare valid URLs and identify immediately invalid entries
            $urlMap = [];
            foreach ($attachments as $attachment) {
                $externalUrl = $attachment->custom_properties['external_url'] 
                    ?? (filter_var($attachment->file_name, FILTER_VALIDATE_URL) ? $attachment->file_name : null)
                    ?? $attachment->original_url 
                    ?? null;

                if (!$externalUrl || !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
                    if ($deleteDead) {
                        $this->safeDetachAndForceDelete($attachment);
                        $deletedDeadCount++;
                        $log[] = [
                            'id' => $attachment->id,
                            'name' => $attachment->name ?: $attachment->file_name,
                            'status' => 'deleted_dead',
                            'reason' => 'Invalid or missing URL',
                        ];
                    } else {
                        $failedCount++;
                        $log[] = [
                            'id' => $attachment->id,
                            'name' => $attachment->name ?: $attachment->file_name,
                            'status' => 'failed',
                            'reason' => 'Invalid URL',
                        ];
                    }
                } else {
                    $urlMap[$attachment->id] = [
                        'attachment' => $attachment,
                        'url' => $externalUrl,
                    ];
                }
            }

            // 2. Perform concurrent parallel HTTP requests using Http::pool
            $poolResponses = [];
            if (!empty($urlMap)) {
                $poolResponses = Http::pool(function (Pool $pool) use ($urlMap) {
                    $requests = [];
                    foreach ($urlMap as $id => $data) {
                        $requests[] = $pool->as("att_{$id}")
                            ->timeout(6)
                            ->connectTimeout(3)
                            ->withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                            ])
                            ->get($data['url']);
                    }
                    return $requests;
                });
            }

            // 3. Process concurrent responses
            foreach ($urlMap as $id => $data) {
                $attachment = $data['attachment'];
                $response = $poolResponses["att_{$id}"] ?? null;

                $isSuccessful = ($response instanceof \Illuminate\Http\Client\Response) && $response->successful();

                if ($isSuccessful) {
                    try {
                        $imageContent = $response->body();
                        $contentType = $response->header('Content-Type') ?: 'image/jpeg';

                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($imageContent) ?: $contentType;

                        $extension = 'jpg';
                        if (str_contains($mimeType, 'png')) $extension = 'png';
                        elseif (str_contains($mimeType, 'webp')) $extension = 'webp';
                        elseif (str_contains($mimeType, 'gif')) $extension = 'gif';
                        elseif (str_contains($mimeType, 'svg')) $extension = 'svg';

                        $baseName = pathinfo($attachment->file_name, PATHINFO_FILENAME);
                        $cleanName = Str::slug($attachment->name ?: $baseName) ?: 'media_' . $attachment->id;
                        $finalFileName = $cleanName . '.' . $extension;
                        $fileSize = strlen($imageContent);

                        // Save in standard Spatie path: storage/app/public/{id}/{fileName}
                        $storageRelativePath = "{$attachment->id}/{$finalFileName}";
                        Storage::disk('public')->put($storageRelativePath, $imageContent);

                        $customProps = $attachment->custom_properties ?: [];
                        $customProps['synced_from'] = 'cloudinary';
                        $customProps['synced_at'] = now()->toIso8601String();
                        unset($customProps['external_url']);

                        $attachment->update([
                            'disk' => 'public',
                            'conversions_disk' => 'public',
                            'file_name' => $finalFileName,
                            'mime_type' => $mimeType,
                            'size' => $fileSize,
                            'custom_properties' => $customProps,
                        ]);

                        $syncedCount++;
                        $log[] = [
                            'id' => $attachment->id,
                            'name' => $attachment->name ?: $finalFileName,
                            'status' => 'synced',
                            'size' => $fileSize,
                        ];
                    } catch (\Throwable $e) {
                        $failedCount++;
                        $log[] = [
                            'id' => $attachment->id,
                            'name' => $attachment->name ?: $attachment->file_name,
                            'status' => 'failed',
                            'reason' => 'Save failed: ' . $e->getMessage(),
                        ];
                    }
                } else {
                    // Dead or unreachable (404, 403, 500, timeout)
                    if ($deleteDead) {
                        $this->safeDetachAndForceDelete($attachment);
                        $deletedDeadCount++;
                        $statusText = ($response instanceof \Illuminate\Http\Client\Response) 
                            ? 'Dead (' . $response->status() . ')' 
                            : 'Unreachable / Timeout';
                        $log[] = [
                            'id' => $attachment->id,
                            'name' => $attachment->name ?: $attachment->file_name,
                            'status' => 'deleted_dead',
                            'reason' => $statusText,
                        ];
                    } else {
                        $failedCount++;
                        $log[] = [
                            'id' => $attachment->id,
                            'name' => $attachment->name ?: $attachment->file_name,
                            'status' => 'failed',
                            'reason' => 'HTTP fetch failed',
                        ];
                    }
                }
            }

            $remainingAfterBatch = max(0, $totalRemaining - ($syncedCount + $deletedDeadCount));

            return response()->json([
                'success' => true,
                'message' => "Batch processed: {$syncedCount} synced in-place, {$deletedDeadCount} dead deleted.",
                'processed_count' => count($attachments),
                'synced_count' => $syncedCount,
                'deleted_dead_count' => $deletedDeadCount,
                'failed_count' => $failedCount,
                'remaining_count' => $remainingAfterBatch,
                'has_more' => $remainingAfterBatch > 0,
                'log' => $log,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'processed_count' => 0,
                'synced_count' => 0,
                'deleted_dead_count' => 0,
                'failed_count' => 0,
                'remaining_count' => 0,
                'has_more' => false,
                'log' => [],
            ], 200);
        }
    }

    protected function safeDetachAndForceDelete($attachment)
    {
        $id = $attachment->id;

        $updates = [
            ['table' => 'products', 'column' => 'product_thumbnail_id'],
            ['table' => 'products', 'column' => 'size_chart_image_id'],
            ['table' => 'products', 'column' => 'product_meta_image_id'],
            ['table' => 'product_images', 'column' => 'attachment_id', 'action' => 'delete'],
            ['table' => 'variations', 'column' => 'variation_image_id'],
            ['table' => 'categories', 'column' => 'category_image_id'],
            ['table' => 'categories', 'column' => 'category_icon_id'],
            ['table' => 'stores', 'column' => 'store_logo_id'],
            ['table' => 'stores', 'column' => 'store_cover_id'],
            ['table' => 'blogs', 'column' => 'blog_thumbnail_id'],
            ['table' => 'blogs', 'column' => 'blog_meta_image_id'],
            ['table' => 'pages', 'column' => 'page_meta_image_id'],
            ['table' => 'reviews', 'column' => 'review_image_id'],
            ['table' => 'refunds', 'column' => 'refund_image_id'],
            ['table' => 'offer_banners', 'column' => 'banner_image_id'],
        ];

        foreach ($updates as $item) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable($item['table']) && 
                    \Illuminate\Support\Facades\Schema::hasColumn($item['table'], $item['column'])) {
                    if (isset($item['action']) && $item['action'] === 'delete') {
                        \Illuminate\Support\Facades\DB::table($item['table'])->where($item['column'], $id)->delete();
                    } else {
                        \Illuminate\Support\Facades\DB::table($item['table'])->where($item['column'], $id)->update([$item['column'] => null]);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore gracefully
            }
        }

        try {
            $attachment->forceDelete();
        } catch (\Throwable $e) {
            try {
                $attachment->delete();
            } catch (\Throwable $e2) {
                // Ignore
            }
        }
    }
}
