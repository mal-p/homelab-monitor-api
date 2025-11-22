<?php
namespace App\Http\Controllers;

use App\Jobs\SendAlarmNotification;
use App\Models\{DeviceData, DeviceParameter};
use App\Services\AlarmService;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Support\Facades\{DB, Log, Validator};
use Illuminate\Validation\Rule;

class DeviceDataController extends Controller
{
    public const int MAX_STORAGE_INSERT_ATTEMPTS = 2;
    public const int MAX_STORAGE_READINGS = 200;

    public function __construct(
        private AlarmService $alarmService,
    ) {}

    /**
     * Fetch time-series data for a paramter in time buckets.
     * @see \App\Http\Controllers\Docs\DeviceDataDocumentation::bucket() for API documentation
     */
    public function bucket(Request $request, string $paramId)
    {
        if ($errors = $this->validateId($paramId)) {
            return $errors;
        }

        $dateFormat = DeviceData::DATETIME_FORMAT;
        $minBucket = DeviceParameter::MIN_BUCKET_SIZE_MINS;
        $maxBucket = DeviceParameter::MAX_BUCKET_SIZE_MINS;

        $validator = Validator::make($request->all(), [
            'start' => ['required', 'string', Rule::date()->format($dateFormat)],
            'end' => ['required', 'string', Rule::date()->format($dateFormat), 'after:start'],
            'bucket_size' => ['nullable', 'integer', "min:{$minBucket}", "max:{$maxBucket}"],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $validated = $validator->validated();

        $start = \DateTime::createFromFormat($dateFormat, $validated['start']);
        $end = \DateTime::createFromFormat($dateFormat, $validated['end']);
        $bucketSizeMinutes = $validated['bucket_size'] ?? 60;

        try {
            $deviceParameter = DeviceParameter::findOrFail($paramId);
            $bucketData = $deviceParameter->bucketData($bucketSizeMinutes, $start, $end);

            return response()->json(
                ['data' => $bucketData],
                Response::HTTP_OK,
            );

        } catch (\TypeError|\InvalidArgumentException $e) {
            return response()->json(
                ['errors' => ['date' => ['Invalid date format']]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($paramId);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'bucket', $paramId);
        }
    }

    /**
     * Store time-series data for a paramter.
     * @see \App\Http\Controllers\Docs\DeviceDataDocumentation::store() for API documentation
     */
    public function store(Request $request, string $paramId)
    {
        if ($errors = $this->validateId($paramId)) {
            return $errors;
        }

        $dateFormat = DeviceData::DATETIME_FORMAT;
        $maxReadings = self::MAX_STORAGE_READINGS;

        $validator = Validator::make($request->all(), [
            'data' => ['required', 'array', 'min:1', "max:{$maxReadings}"],
            'data.*.value' => ['required', 'numeric'],
            'data.*.time' => ['required', 'string', Rule::date()->format($dateFormat)],
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $validated = $validator->validated();

        // Remove duplicates and prepare data
        $uniqueData = collect($validated['data'])
            ->unique('time')
            ->map(fn($reading) => [
                'time' => $reading['time'],
                'value' => $reading['value'],
                'parameter_id' => $paramId,
            ])
            ->sortBy('time')
            ->values()
            ->all();
    
        try {
            /* DB query debug */
            // DB::enableQueryLog();

            DB::transaction(function () use ($paramId, $uniqueData) {
                /* Pessimistic locking is not really necessary for homelab usage
                 *     SELECT * FROM "device_parameters" WHERE "device_parameters"."id" = ? LIMIT 1 FOR UPDATE;
                 */
                $deviceParameter = DeviceParameter::lockForUpdate()->findOrFail($paramId);

                /* Ingest dataset is limited to MAX_STORAGE_READINGS, no need for chunking.
                 * Ignore DB unique constraint on (parameter_id, time)
                 *
                 * INSERT INTO "device_data" ("parameter_id", "time", "value")
                 * VALUES (?, ?, ?), ...
                 * ON CONFLICT DO NOTHING;
                 */
                DeviceData::insertOrIgnore($uniqueData);

                $alarmChanges = $this->alarmService->processAlarmChanges($deviceParameter, $uniqueData);

                if ($alarmChanges['stateChanged']) {
                    $initialAlarmState = $deviceParameter->alarm_active;

                    $deviceParameter->update([
                        'alarm_active' => $alarmChanges['finalAlarmActive'],
                        'alarm_updated_at' => $alarmChanges['finalUpdateTime'],
                    ]);

                    /* Only queue an alarm message if the final state differs.
                     * Not interested in sending multiple flip-flop messages.
                     */
                    if ($alarmChanges['finalAlarmActive'] !== $initialAlarmState) {
                        // Deferred dispatch
                        SendAlarmNotification::dispatch($paramId, $alarmChanges['finalParamValue'])
                            ->onConnection('deferred')
                            ->afterCommit();
                    }
                }
            }, self::MAX_STORAGE_INSERT_ATTEMPTS);

            /* End DB query debug */
            // $queries = DB::getQueryLog();
            // foreach ($queries as $idx => $query) {
            //     Log::debug("DB query {$idx} during DeviceData::store()", $query);
            // }

            return response()->json(
                ['message' => 'Data stored successfully', 'count' => count($uniqueData)],
                Response::HTTP_CREATED,
            );

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($paramId);
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, 'store', $paramId);
        }
    }

    /*
     * Private helper functions
     */
    private function notFoundResponse(string $id): JsonResponse
    {
        return response()->json(
            ['errors' => ['device_parameter' => ["Parameter with ID {$id} not found"]]],
            Response::HTTP_NOT_FOUND,
        );
    }

    private function databaseErrorResponse(QueryException $e, string $method, string|null $id = null): JsonResponse
    {
        Log::error('Database operation failed', [
            'route' => "DeviceDataController::{$method}",
            'device_parameter_id' => $id,
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
                ['errors' => ['id' => ['Invalid device parameter ID']]],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
        return null;
    }
}
