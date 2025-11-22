<?php

namespace App\Http\Controllers;

use App\Models\DeviceParameter;
use App\Http\Requests\{StoreDeviceParameterRequest, UpdateDeviceParameterRequest};

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class DeviceParameterController extends Controller
{
    private const RESULTS_PER_PAGE = 15;
    private const MAX_PAGE_NUMBER = 1000;

    /**
     * List device parameters.
     * @see \App\Http\Controllers\Docs\DeviceParameterDocumentation::index() for API documentation
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
            $devParams = DeviceParameter::orderBy('id')->simplePaginate(self::RESULTS_PER_PAGE);

            $devParamData = $devParams->map(function ($param) {
                return [
                    'id' => $param->id,
                    'device_id' => $param->device_id,
                    'name' => $param->name,
                    'unit' => $param->unit,
                    'alarm_active' => $param->alarm_active,
                ];
            });

            $links = $this->generatePaginationLinks($devParams);

            return response()->json(
                ['device_parameters' => $devParamData, 'links' => $links],
                Response::HTTP_OK,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'index');
        }
    }

    /**
     * Store a newly created device parameter.
     * @see \App\Http\Controllers\Docs\DeviceParameterDocumentation::store() for API documentation
     */
    public function store(StoreDeviceParameterRequest $request)
    {
        try {
            $deviceParameter = DeviceParameter::create($request->validated());

            return response()->json(
                ['device_parameter' => $deviceParameter],
                Response::HTTP_CREATED,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'store');
        }
    }

    /**
     * Show all information for the specified device parameter.
     * @see \App\Http\Controllers\Docs\DeviceParameterDocumentation::show() for API documentation
     */
    public function show(string $id)
    {
        if ($errors = $this->validateId($id)) {
            return $errors;
        }

        try {
            $deviceParameter = DeviceParameter::with('device')->findOrFail($id);

            return response()->json(
                ['device_parameter' => [
                    'id' => $deviceParameter->id,
                    'name' => $deviceParameter->name,
                    'unit' => $deviceParameter->unit ?? '',
                    'alarm_type' => $deviceParameter->alarm_type,
                    'alarm_trigger' => $deviceParameter->alarm_trigger,
                    'alarm_hysteresis' => $deviceParameter->alarm_hysteresis,
                    'alarm_active' => $deviceParameter->alarm_active,
                    'alarm_updated_at' => $deviceParameter->alarm_updated_at,
                    'device' => $deviceParameter->device,
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
     * Update the specified device parameter.
     * @see \App\Http\Controllers\Docs\DeviceParameterDocumentation::update() for API documentation
     */
    public function update(UpdateDeviceParameterRequest $request, string $id)
    {
        if ($errors = $this->validateId($id)) {
            return $errors;
        }

        try {
            $deviceParameter = DeviceParameter::findOrFail($id);

            $validated = $request->validated();
            $dtNow = new \DateTime('now', new \DateTimeZone('UTC'));
            $validated['alarm_updated_at'] = $dtNow->format('Y-m-d\TH:i:s\Z');

            $deviceParameter->update($validated);

            return response()->noContent(); // return 204

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($id);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'update', $id);
        }
    }

    /**
     * Remove the specified device parameter from storage.
     * @see \App\Http\Controllers\Docs\DeviceParameterDocumentation::destroy() for API documentation
     */
    public function destroy(string $id)
    {
        if ($errors = $this->validateId($id)) {
            return $errors;
        }

        try {
            $deviceParameter = DeviceParameter::findOrFail($id);
            $deviceParameter->delete();

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
            ['errors' => ['device_parameter' => ["Parameter with ID {$id} not found"]]],
            Response::HTTP_NOT_FOUND
        );
    }

    private function databaseErrorResponse(QueryException $e, string $method, string|null $id = null): JsonResponse
    {
        Log::error('Database operation failed', [
            'route' => "DeviceParameterController::{$method}",
            'device_parameter_id' => $id,
            'exception' => $e->getMessage(),
        ]);

        return response()->json(
            ['errors' => ['server' => ['Database error occurred']]],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    private function validateId(string $id): ?JsonResponse
    {
        if (false === filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            return response()->json(
                ['errors' => ['id' => ['Invalid device parameter ID']]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        return null;
    }
}
