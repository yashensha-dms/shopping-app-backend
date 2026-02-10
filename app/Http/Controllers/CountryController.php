<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use App\Repositories\Eloquents\CountryRepository;

class CountryController extends Controller
{
    public $repository;

    public function __construct(CountryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/country",
     *      operationId="getCountries",
     *      tags={"Countries & States"},
     *      summary="Get list of countries",
     *      description="Returns all countries with their states. Use this for shipping address forms.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="name", type="string", example="United States"),
     *                  @OA\Property(property="code", type="string", example="US"),
     *                  @OA\Property(property="currency", type="string", example="USD"),
     *                  @OA\Property(property="phone_code", type="string", example="+1"),
     *                  @OA\Property(property="state", type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string", example="California"),
     *                          @OA\Property(property="country_id", type="integer")
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index()
    {
        return $this->repository->with('state')->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @OA\Get(
     *      path="/country/{id}",
     *      operationId="getCountryById",
     *      tags={"Countries & States"},
     *      summary="Get country by ID",
     *      description="Returns a single country with all its states.",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Country not found")
     * )
     */
    public function show(Country $country)
    {
        return $this->repository->show($country->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
