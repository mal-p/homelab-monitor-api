<?php

use App\Models\DeviceParameter;
use App\Http\Controllers\DeviceDataController;
use App\Services\AlarmService;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// Stub class constants for Models
class DeviceParameterStub
{
    const ALARM_TYPES = ['none', 'low', 'high'];
    const MIN_BUCKET_SIZE_MINS = 5;
    const MAX_BUCKET_SIZE_MINS = 1440;
}

beforeEach(function () {
    $this->alarmService = Mockery::mock(AlarmService::class);
    $this->controller = new DeviceDataController($this->alarmService);
    
    $this->paramId = '1';
    $this->validStart = '2023-10-24T00:00:00Z';
    $this->validEnd = '2023-10-24T12:00:00Z';

    $this->deviceParameterAlias = Mockery::mock('alias:' . DeviceParameter::class, 'DeviceParameterStub');
});

afterEach(function () {
    Mockery::close();
});

describe('validation', function () {
    it('returns error for invalid parameter ID', function () {
        $request = request();
        $response = $this->controller->bucket($request, 'invalid_param_id');

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.id');
    });

    it('returns error for non-positive parameter ID', function () {
        $request = request();
        $response = $this->controller->bucket($request, '0');

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.id');
    });

    it('returns error when start date is missing', function () {
        $request = request()->merge(['end' => $this->validEnd]);
        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.start');
    });

    it('returns error when end date is missing', function () {
        $request = request()->merge(['start' => $this->validStart]);
        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.end');
    });

    it('returns error when start date has invalid format', function () {
        $request = request()->merge([
            'start' => '2023/01/01 00:00:00',
            'end' => $this->validEnd,
        ]);
        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors');
    });

    it('returns error when end date is before start date', function () {
        $request = request()->merge([
            'start' => $this->validEnd,
            'end' => $this->validStart,
        ]);
        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.end');
    });

    it('returns error when bucket_size is below minimum', function () {
        $request = request()->merge([
            'start' => $this->validStart,
            'end' => $this->validEnd,
            'bucket_size' => DeviceParameter::MIN_BUCKET_SIZE_MINS - 1,
        ]);
        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.bucket_size');
    });

    it('returns error when bucket_size is above maximum', function () {
        $request = request()->merge([
            'start' => $this->validStart,
            'end' => $this->validEnd,
            'bucket_size' => DeviceParameter::MAX_BUCKET_SIZE_MINS + 1,
        ]);
        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.bucket_size');
    });
});

describe('successful responses', function () {
    it('returns bucketed data successfully with default bucket size', function () {
        $bucketData = [[
            'bucket_start' => "2023-01-01 12:00:00+00",
            'count' => 2,
            'min_value' => 0.19,
            'max_value' => 0.21,
            'avg_value' => 0.20,
        ]];

        $deviceParameterInstance = Mockery::mock();
        $deviceParameterInstance->shouldReceive('bucketData')
            ->once()
            ->with(60, Mockery::type(\DateTime::class), Mockery::type(\DateTime::class))
            ->andReturn($bucketData);

        $this->deviceParameterAlias->shouldReceive('findOrFail')
            ->once()
            ->with($this->paramId)
            ->andReturn($deviceParameterInstance);

        $request = request()->merge([
            'start' => $this->validStart,
            'end' => $this->validEnd,
            // default bucket size
        ]);

        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($response->getData(true))->toHaveKey('data')
            ->and($response->getData(true)['data'])->toBe($bucketData);
    });
});

describe('error handling', function () {
    it('returns not found when parameter does not exist', function () {
        $this->deviceParameterAlias->shouldReceive('findOrFail')
            ->once()
            ->with($this->paramId)
            ->andThrow(new ModelNotFoundException());
    
        $request = request()->merge([
            'start' => $this->validStart,
            'end' => $this->validEnd,
        ]);

        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND)
            ->and($response->getData(true))->toHaveKey('errors.device_parameter');
    });

    it('handles TypeError from invalid date format', function () {
        $deviceParameterInstance = Mockery::mock();
        $deviceParameterInstance->shouldReceive('bucketData')
            ->once()
            ->andThrow(new \TypeError('Invalid date'));

        $this->deviceParameterAlias->shouldReceive('findOrFail')
            ->once()
            ->with($this->paramId)
            ->andReturn($deviceParameterInstance);

        $request = request()->merge([
            'start' => $this->validStart,
            'end' => $this->validEnd,
        ]);
        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->and($response->getData(true))->toHaveKey('errors.date');
    });

    it('handles database query exception', function () {
        Log::shouldReceive('error')->once();
        
        $queryException = new QueryException(
            'test_conn',
            'SELECT COUNT(*) FROM bad_table;',
            [],
            new \Exception('Database error'),
        );

        $this->deviceParameterAlias->shouldReceive('findOrFail')
            ->once()
            ->with($this->paramId)
            ->andThrow($queryException);

        $request = request()->merge([
            'start' => $this->validStart,
            'end' => $this->validEnd,
        ]);

        $response = $this->controller->bucket($request, $this->paramId);

        expect($response->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->and($response->getData(true))->toHaveKey('errors.server');
    });
});
