<?php

namespace App\Http\Controllers;

use App\Models\Device;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\{Log, Validator};

class DeviceController extends Controller
{
    private const RESULTS_PER_PAGE = 15;
    private const MAX_PAGE_NUMBER = 1000;

    /**
     * List devices.
     * @see \App\Http\Controllers\Docs\DeviceDocumentation::index() for API documentation
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
            $devices = Device::orderBy('id')->simplePaginate(self::RESULTS_PER_PAGE);

            $deviceData = $devices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'is_active' => $device->is_active,
                    'type' => $device->deviceType->name, // Device Model automatically eager loads deviceType
                ];
            });

            $links = $this->generatePaginationLinks($devices);

            return response()->json(
                ['devices' => $deviceData, 'links' => $links],
                Response::HTTP_OK,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'index');
        }
    }

    /**
     * Store a newly created device.
     * @see \App\Http\Controllers\Docs\DeviceDocumentation::store() for API documentation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_id' => ['required', 'integer', 'exists:pgsql.device_types,id'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'serial_number' => ['required', 'string', 'min:1', 'max:100', 'unique:devices,serial_number'],
            'mpan' => ['nullable', 'string', 'max:100', 'unique:devices,mpan'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $device = Device::create($validator->validated());
            // Refetch device to load (empty) parameters and type relationship
            $device = Device::with('deviceParameters')->find($device->id);

            return response()->json(
                ['device' => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'serial_number' => $device->serial_number,
                    'mpan' => $device->mpan,
                    'location' => $device->location ?? '',
                    'description' => $device->description ?? '',
                    'is_active' => $device->is_active,
                    'type' => $device->deviceType->name, // Device Model always eager loads deviceType
                    'parameters' => $device->deviceParameters,
                ]],
                Response::HTTP_CREATED,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'store');
        }
    }

    /**
     * Show all information for the specified device.
     * @see \App\Http\Controllers\Docs\DeviceDocumentation::show() for API documentation
     */
    public function show(string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        try {
            $device = Device::with('deviceParameters')->findOrFail($id);

            return response()->json(
                ['device' => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'serial_number' => $device->serial_number,
                    'mpan' => $device->mpan,
                    'location' => $device->location ?? '',
                    'description' => $device->description ?? '',
                    'is_active' => $device->is_active,
                    'type' => $device->deviceType->name, // Device Model always eager loads deviceType
                    'parameters' => $device->deviceParameters,
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
     * Update the specified device.
     * @see \App\Http\Controllers\Docs\DeviceDocumentation::update() for API documentation
     */
    public function update(Request $request, string $id)
    {
        // curl -X POST http://127.0.0.1/devices/1 \
        //   -H "Content-Type: application/json" \
        //   -d '{"_method":"PUT","name":"Updated Device"}'

        if ($error = $this->validateId($id)) {
            return $error;
        }

        $validator = Validator::make($request->all(), [
            'type_id' => ['required', 'integer', 'exists:pgsql.device_types,id'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'serial_number' => ['required', 'string', 'min:1', 'max:100', "unique:devices,serial_number,{$id}"],
            'mpan' => ['nullable', 'string', 'max:100', "unique:devices,mpan,{$id}"],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $device = Device::findOrFail($id);
            $device->update($validator->validated());
            
            return response()->noContent(); // return 204

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'update', $id);
        }
    }

    /**
     * Remove the specified device from storage.
     * @see \App\Http\Controllers\Docs\DeviceDocumentation::destroy() for API documentation
     */
    public function destroy(string $id)
    {
        // curl -X POST http://127.0.0.1/devices/1 \
        //   -H "Content-Type: application/json" \
        //   -d '{"_method":"DELETE","name":"Deleted Device"}'

        if ($error = $this->validateId($id)) {
            return $error;
        }

        try {
            $device = Device::findOrFail($id);
            $device->delete();

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
            ['errors' => ['device' => ["Device with ID {$id} not found"]]],
            Response::HTTP_NOT_FOUND,
        );
    }

    private function databaseErrorResponse(QueryException $e, string $method, string|null $id = null): JsonResponse
    {
        Log::error('Database operation failed', [
            'route' => "DeviceController::{$method}",
            'device_id' => $id,
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
                ['errors' => ['id' => ['Invalid device ID']]],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
        return null;
    }
}
