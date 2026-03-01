<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Http\Controllers\Concerns\GeneratesApiPaginationLinks;
use App\Http\Controllers\Concerns\GeneratesDatabaseErrorResponses;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    use GeneratesApiPaginationLinks, GeneratesDatabaseErrorResponses;

    private const RESULTS_PER_PAGE = 15;
    private const MAX_PAGE_NUMBER = 1000;

    /**
     * List locations.
     * @see \App\Http\Controllers\Docs\LocationDocumentation::index() for API documentation
     */
    public function index(Request $request)
    {
        $pageNumber = filter_var(
            $request->page,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => self::MAX_PAGE_NUMBER]]
        ) ?: 1;

        try {
            Paginator::currentPageResolver(fn() => $pageNumber);

            $locations = Location::select(['id', 'name', 'description'])
                ->orderBy('id')
                ->simplePaginate(self::RESULTS_PER_PAGE)
                ->through(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'description' => $location->description,
                    ];
                });

            $links = $this->generatePaginationLinks($locations);

            return response()->json(
                ['locations' => $locations->items(), 'links' => $links],
                Response::HTTP_OK,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__);
        }
    }

    /**
     * Store a newly created location.
     * @see \App\Http\Controllers\Docs\LocationDocumentation::store() for API documentation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:255', "unique:locations,name"],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $location = Location::create($validator->validated());

            return response()->json(
                ['location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'description' => $location->description,
                ]],
                Response::HTTP_CREATED,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__);
        }
    }

    /**
     * Show details for the specified location, including its devices.
     * @see \App\Http\Controllers\Docs\LocationDocumentation::show() for API documentation
     */
    public function show(string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        try {
            $location = Location::with('devices:id,location_id,name,is_active')->findOrFail($id);

            return response()->json(
                ['location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'description' => $location->description,
                    'devices' => $location->devices->map(function ($device) {
                        return [
                            'id' => $device->id,
                            'name' => $device->name,
                            'is_active' => $device->is_active,
                        ];
                    })->values(),
                ]],
                Response::HTTP_OK,
            );

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__, ['location_id' => $id]);
        }
    }

    /**
     * Update the specified location.
     * @see \App\Http\Controllers\Docs\LocationDocumentation::update() for API documentation
     */
    public function update(Request $request, string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:255', "unique:locations,name,{$id}"],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $location = Location::findOrFail($id);
            $location->update($validator->validated());

            return response()->noContent(); // return 204

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__, ['location_id' => $id]);
        }
    }

    /**
     * Remove the specified location from storage.
     * @see \App\Http\Controllers\Docs\LocationDocumentation::destroy() for API documentation
     */
    public function destroy(string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        try {
            $location = Location::findOrFail($id);
            // devices FK constraint on locations is ON DELETE SET NULL
            $location->delete();

            return response()->noContent(); // return 204

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__, ['location_id' => $id]);
        }
    }

    /*
     * Private helper functions
     */
    private function notFoundResponse(string $id): JsonResponse
    {
        return response()->json(
            ['errors' => ['location' => ["Location with ID {$id} not found"]]],
            Response::HTTP_NOT_FOUND,
        );
    }

    private function validateId(string $id): ?JsonResponse
    {
        if (false === filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            return response()->json(
                ['errors' => ['id' => ['Invalid location ID']]],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
        return null;
    }
}
