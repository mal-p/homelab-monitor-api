<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\{Log, Validator};

class DeviceTypeController extends Controller
{
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:device_types,name'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $devType = DeviceType::create($validator->validated());

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
    public function update(Request $request, string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:255', "unique:device_types,name,{$id}"],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $devType = DeviceType::findOrFail($id);
            $devType->update($validator->validated());
            
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
    private function generatePaginationLinks(Paginator $resource): array
    {
        $links = [];
        $currentPage = $resource->currentPage();
        $path = $resource->path();

        if ($resource->hasMorePages()) {
            $nextPage = $currentPage + 1;

            $links[] = [
                'href' => "{$path}?page={$nextPage}",
                'rel' => 'next',
                'method' => 'GET',
            ];
        }

        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;

            $links[] = [
                'href' => "{$path}?page={$prevPage}",
                'rel' => 'prev',
                'method' => 'GET',
            ];
        }

        return $links;
    }

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
