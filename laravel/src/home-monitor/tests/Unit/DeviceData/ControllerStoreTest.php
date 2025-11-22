<?php

use App\Models\{DeviceData, DeviceParameter};
use App\Http\Controllers\DeviceDataController;
use App\Services\AlarmService;

use App\Jobs\SendAlarmNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\{Bus, DB, Log};

// Stub class constants for Models
class DeviceDataStub
{
    const DATETIME_FORMAT = 'Y-m-d\TH:i:s\Z';
}

beforeEach(function () {
    $this->alarmService = Mockery::mock(AlarmService::class);
    $this->controller = new DeviceDataController($this->alarmService);
    
    $this->paramId = '1';
    $this->validStart = '2023-10-24T00:00:00Z';
    $this->validEnd = '2023-10-24T12:00:00Z';

    $this->deviceDataAlias = Mockery::mock('alias:' . DeviceData::class, 'DeviceDataStub');
});

afterEach(function () {
    Mockery::close();
});

describe('validation', function () {
    it('returns error for invalid parameter ID', function () {
        $request = request();
        $response = $this->controller->store($request, 'abc');

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.id');
    });

    it('returns error when data is missing', function () {
        $request = request()->merge([]);
        $response = $this->controller->store($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.data');
    });

    it('returns error when data is empty array', function () {
        $request = request()->merge(['data' => []]);
        $response = $this->controller->store($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.data');
    });

    it('returns error when data exceeds maximum readings', function () {
        $data = array_fill(0, DeviceDataController::MAX_STORAGE_READINGS + 1, [
            'value' => 25.5,
            'time' => $this->validStart,
        ]);
    
        $request = request()->merge(['data' => $data]);
        $response = $this->controller->store($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.data');
    });

    it('returns error when value is missing', function () {
        $request = request()->merge([
            'data' => [
                ['time' => $this->validStart],
            ],
        ]);
        $response = $this->controller->store($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors');
    });

    it('returns error when value is not numeric', function () {
        $request = request()->merge([
            'data' => [
                ['value' => 'invalid', 'time' => $this->validStart],
            ],
        ]);
        $response = $this->controller->store($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors');
    });

    it('returns error when time is missing', function () {
        $request = request()->merge([
            'data' => [
                ['value' => 25.5],
            ],
        ]);
        $response = $this->controller->store($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors');
    });

    it('returns error when time format is invalid', function () {
        $request = request()->merge([
            'data' => [
                ['value' => 25.5, 'time' => '2023/01/01 00:00:00'],
            ],
        ]);
        $response = $this->controller->store($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors');
    });
});

describe('successful data storage', function () {
    it('stores single reading successfully', function () {
        // Prevent Laravel from dispatching jobs
        Bus::fake();

        $deviceParameter = DeviceParameter::factory()->create();

        $this->deviceDataAlias->shouldReceive('insertOrIgnore')->once();

        $this->alarmService->shouldReceive('processAlarmChanges')
            ->once()
            ->andReturn(['stateChanged' => false]);

        $request = request()->merge([
            'data' => [
                ['value' => 25.5, 'time' => '2023-01-01T00:00:00Z'],
            ],
        ]);
        $response = $this->controller->store($request, (string) $deviceParameter->id);

        expect($response->getStatusCode())->toBe(Response::HTTP_CREATED)
            ->and($response->getData(true))->toHaveKey('message')
            ->and($response->getData(true)['count'])->toBe(1);
    });

    it('stores multiple readings successfully', function () {
        Bus::fake();
        
        $deviceParameter = DeviceParameter::factory()->create();

        // Single INSERT statement with multiple values
        $this->deviceDataAlias->shouldReceive('insertOrIgnore')->once();

        $this->alarmService->shouldReceive('processAlarmChanges')
            ->once()
            ->andReturn(['stateChanged' => false]);

        $request = request()->merge([
            'data' => [
                ['value' => 25.5, 'time' => '2023-01-01T00:00:00Z'],
                ['value' => 26.0, 'time' => '2023-01-01T01:00:00Z'],
                ['value' => 24.8, 'time' => '2023-01-01T02:00:00Z'],
            ],
        ]);
        $response = $this->controller->store($request, (string) $deviceParameter->id);

        expect($response->getStatusCode())->toBe(Response::HTTP_CREATED)
            ->and($response->getData(true)['count'])->toBe(3);
    });

    it('removes duplicate timestamps', function () {
        Bus::fake();

        $deviceParameter = DeviceParameter::factory()->create();

        $this->deviceDataAlias->shouldReceive('insertOrIgnore')->once();

        $this->alarmService->shouldReceive('processAlarmChanges')
            ->once()
            ->andReturn(['stateChanged' => false]);

        $request = request()->merge([
            'data' => [
                ['value' => 25.5, 'time' => '2023-01-01T00:00:00Z'],
                ['value' => 26.0, 'time' => '2023-01-01T00:00:00Z'], // duplicate
                ['value' => 24.8, 'time' => '2023-01-01T01:00:00Z'],
            ],
        ]);
        $response = $this->controller->store($request, (string) $deviceParameter->id);

        expect($response->getStatusCode())->toBe(Response::HTTP_CREATED)
            ->and($response->getData(true)['count'])->toBe(2);
    });
});

describe('alarm handling', function () {
    it('updates alarm state when alarm activates', function () {
        // Prevent Laravel from dispatching jobs
        Bus::fake();
        
        $deviceParameter = DeviceParameter::factory()->create([
            'alarm_active' => false,
        ]);
        
        $this->deviceDataAlias->shouldReceive('insertOrIgnore')->once();

        $this->alarmService->shouldReceive('processAlarmChanges')
            ->once()
            ->andReturn([
                'stateChanged' => true,
                'finalAlarmActive' => true,
                'finalUpdateTime' => now(),
                'finalParamValue' => 35.0,
            ]);

        $request = request()->merge([
            'data' => [
                ['value' => 35.0, 'time' => $this->validStart],
            ],
        ]);
        
        $response = $this->controller->store($request, (string) $deviceParameter->id);

        expect($response->getStatusCode())->toBe(Response::HTTP_CREATED);
        
        Bus::assertDispatched(SendAlarmNotification::class);
    });

    it('updates alarm state when alarm deactivates', function () {
        Bus::fake();
        
        $deviceParameter = DeviceParameter::factory()->create([
            'alarm_active' => true,
        ]);
        
        $this->deviceDataAlias->shouldReceive('insertOrIgnore')->once();

        $this->alarmService->shouldReceive('processAlarmChanges')
            ->once()
            ->andReturn([
                'stateChanged' => true,
                'finalAlarmActive' => false,
                'finalUpdateTime' => now(),
                'finalParamValue' => 20.0,
            ]);

        $request = request()->merge([
            'data' => [
                ['value' => 20.0, 'time' => $this->validStart],
            ],
        ]);
        
        $response = $this->controller->store($request, (string) $deviceParameter->id);

        expect($response->getStatusCode())->toBe(Response::HTTP_CREATED);
        
        Bus::assertDispatched(SendAlarmNotification::class);
    });

    it('does not dispatch job when alarm state flip-flops back to initial', function () {
        Bus::fake();
        
        $deviceParameter = DeviceParameter::factory()->create([
            'alarm_active' => false,
        ]);
        
        $this->deviceDataAlias->shouldReceive('insertOrIgnore')->once();

        $this->alarmService->shouldReceive('processAlarmChanges')
            ->once()
            ->andReturn([
                'stateChanged' => true,
                'finalAlarmActive' => false, // Same as initial
                'finalUpdateTime' => now(),
                'finalParamValue' => 25.0,
            ]);

        $request = request()->merge([
            'data' => [
                ['value' => 25.0, 'time' => $this->validStart],
            ],
        ]);
        
        $response = $this->controller->store($request, (string) $deviceParameter->id);

        expect($response->getStatusCode())->toBe(Response::HTTP_CREATED);
        
        Bus::assertNotDispatched(SendAlarmNotification::class);
    });
});

describe('error handling', function () {
    it('returns not found when parameter does not exist', function () {
        $request = request()->merge([
            'data' => [
                ['value' => 25.5, 'time' => $this->validStart],
            ],
        ]);

        $response = $this->controller->store($request, '999');

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND)
            ->and($response->getData(true))->toHaveKey('errors.device_parameter');
    });

    it('handles database query exception', function () {
        Log::shouldReceive('error')->once();
        
        $deviceParameter = DeviceParameter::factory()->create();

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new QueryException(
                'test_conn',
                'SELECT COUNT(*) FROM bad_table;',
                [],
                new \Exception('Database error')
            ));

        $request = request()->merge([
            'data' => [
                ['value' => 25.5, 'time' => $this->validStart],
            ],
        ]);
        
        $response = $this->controller->store($request, (string) $deviceParameter->id);

        expect($response->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->and($response->getData(true))->toHaveKey('errors.server');
    });
});
