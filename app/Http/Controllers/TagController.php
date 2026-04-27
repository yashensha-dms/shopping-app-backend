<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Requests\CreateTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Repositories\Eloquents\TagRepository;

class TagController extends Controller
{
    public $repository;

    public function __construct(TagRepository $repository)
    {
        $this->authorizeResource(Tag::class,'tag', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    
    public function index(Request $request)
    {
        $tags = $this->filter($this->repository, $request);
        return $tags->latest('created_at')->paginate($request->paginate ?? $tags->count());
    }

    
    public function store(CreateTagRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function show(Tag $tag)
    {
        return $this->repository->show($tag->id);
    }

    
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        return $this->repository->update($request->all(), $tag->getId($request));
    }

    
    public function destroy(Request $request, Tag $tag)
    {
        return $this->repository->destroy($tag->getId($request));
    }

    
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function getTagsExportUrl(Request $request)
    {
        return $this->repository->getTagsExportUrl($request);
    }

    
    public function import()
    {
        return $this->repository->import();
    }

    
    public function export()
    {
        return $this->repository->export();
    }

    public function filter($tags, $request)
    {
        if ($request->ids) {
            $ids = explode(',',$request->ids);
            $tags = $tags->findWhereIn('id',$ids);
        }

        if ($request->type) {
            $tags = $this->repository->whereType($request->type);
        }

        if ($request->field && $request->sort) {
            $tags = $tags->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $tags = $tags->where('status',$request->status);
        }

        return $tags;
    }
}
