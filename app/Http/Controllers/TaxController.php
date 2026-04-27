<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Tax;
use Illuminate\Http\Request;
use App\Http\Requests\CreateTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\TaxRepository;

class TaxController extends Controller
{
    protected $repository;

    public function __construct(TaxRepository $repository)
    {
        $this->authorizeResource(Tax::class, 'tax', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    
    public function index(Request $request)
    {
        try {

            $taxes = $this->filter($this->repository, $request);
            return $taxes->latest('created_at')->paginate($request->paginate ?? $taxes->count());

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }

    }

    
    public function store(CreateTaxRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function show(Tax $tax)
    {
        return $this->repository->show($tax->id);
    }

    
    public function update(UpdateTaxRequest $request, Tax $tax)
    {
        return $this->repository->update($request->all(), $tax->getId($request));
    }

    
    public function destroy(Request $request, Tax $tax)
    {
        return  $this->repository->destroy($tax->getId($request));
    }

    
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($taxes, $request)
    {
        if ($request->field && $request->sort) {
            $taxes = $taxes->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $taxes = $taxes->where('status',$request->status);
        }

        return $taxes;
    }
}
