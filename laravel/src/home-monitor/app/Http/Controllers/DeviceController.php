<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Http\Controllers\Concerns\GeneratesApiPaginationLinks;
use App\Http\Controllers\Concerns\GeneratesDatabaseErrorResponses;
use App\Http\Requests\{StoreDeviceRequest, UpdateDeviceRequest};

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Pagination\Paginator;

class DeviceController extends Controller
{
    use GeneratesApiPaginationLinks, GeneratesDatabaseErrorResponses;

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
    public function store(StoreDeviceRequest $request)
    {
        try {
            $device = Device::create($request->validated());
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
            return $this->databaseErrorResponse($e, 'show', ['device_id' => $id]);
        }
    }

    /**
     * Update the specified device.
     * @see \App\Http\Controllers\Docs\DeviceDocumentation::update() for API documentation
     */
    public function update(UpdateDeviceRequest $request, string $id)
    {
        if ($error = $this->validateId($id)) {
            return $error;
        }

        try {
            $device = Device::findOrFail($id);
            $device->update($request->validated());
            
            return response()->noContent(); // return 204

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'update', ['device_id' => $id]);
        }
    }

    /**
     * Remove the specified device from storage.
     * @see \App\Http\Controllers\Docs\DeviceDocumentation::destroy() for API documentation
     */
    public function destroy(string $id)
    {
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
            return $this->databaseErrorResponse($e, 'destroy', ['device_id' => $id]);
        }
    }

    /*
     * Private helper functions
     */
    private function notFoundResponse(string $id): JsonResponse
    {
        return response()->json(
            ['errors' => ['device' => ["Device with ID {$id} not found"]]],
            Response::HTTP_NOT_FOUND,
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
