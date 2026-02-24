<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Http\Controllers\Concerns\GeneratesApiPaginationLinks;
use App\Http\Requests\{StoreDeviceTypeRequest, UpdateDeviceTypeRequest};

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class DeviceTypeController extends Controller
{
    use GeneratesApiPaginationLinks;

    private const RESULTS_PER_PAGE = 15;
    private const MAX_PAGE_NUMBER = 1000;

    /**
     * List device types.
     * @see \App\Http\Controllers\Docs\DeviceTypeDocumentation::index() for API documentation
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
            $deviceTypes = DeviceType::select(['id', 'name', 'description'])
                ->orderBy('id')
                ->simplePaginate(self::RESULTS_PER_PAGE);

            $deviceTypeData = $deviceTypes->map(function ($devType) {
                return [
                    'id' => $devType->id,
                    'name' => $devType->name,
                    'description' => $devType->description,
                ];
            });

            $links = $this->generatePaginationLinks($deviceTypes);

            return response()->json(
                ['device_types' => $deviceTypeData, 'links' => $links],
                Response::HTTP_OK,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'index');
        }
    }

    /**
     * Store a newly created device type.
     * @see \App\Http\Controllers\Docs\DeviceTypeDocumentation::store() for API documentation
     */
    public function store(StoreDeviceTypeRequest $request)
    {
        try {
            $devType = DeviceType::create($request->validated());

            return response()->json(
                ['device_type' => $devType],
                Response::HTTP_CREATED,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'store');
        }
    }

    /**
     * Show all information for the specified device type.
     * @see \App\Http\Controllers\Docs\DeviceTypeDocumentation::show() for API documentation
     */
    public function show(string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        try {
            $devType = DeviceType::findOrFail($id);

            return response()->json(
                ['device_type' => [
                    'id' => $devType->id,
                    'name' => $devType->name,
                    'description' => $devType->description,
                ]],
                Response::HTTP_OK,
            );

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'show', $id);
        }
    }

    /**
     * Update the specified device type.
     * @see \App\Http\Controllers\Docs\DeviceTypeDocumentation::update() for API documentation
     */
    public function update(UpdateDeviceTypeRequest $request, string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        try {
            $devType = DeviceType::findOrFail($id);
            $devType->update($request->validated());
            
            return response()->noContent(); // return 204

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'update', $id);
        }
    }

    /**
     * Remove the specified device type from storage.
     * @see \App\Http\Controllers\Docs\DeviceTypeDocumentation::destroy() for API documentation
     */
    public function destroy(string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        try {
            $devType = DeviceType::findOrFail($id);
            $devType->delete();

            return response()->noContent(); // return 204

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'destroy', $id);
        }
    }

    /*
     * Private helper functions
     */
    private function notFoundResponse(string $id): JsonResponse
    {
        return response()->json(
            ['errors' => ['device_type' => ["Device type with ID {$id} not found"]]],
            Response::HTTP_NOT_FOUND,
        );
    }

    private function databaseErrorResponse(QueryException $e, string $method, string|null $id = null): JsonResponse
    {
        Log::error('Database operation failed', [
            'route' => "DeviceTypeController::{$method}",
            'device_type_id' => $id,
            'exception' => $e->getMessage(),
        ]);

        return response()->json(
            ['errors' => ['server' => ['Database error occurred']]],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    private function validateId(string $id): ?JsonResponse
    {
        if (false === filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            return response()->json(
                ['errors' => ['id' => ['Invalid device type ID']]],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
        return null;
    }
}
