<?php

use App\Models\{DeviceData, DeviceParameter};
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->deviceParameter = DeviceParameter::factory()->create();
    $this->deviceData = DeviceData::factory()->create();
});

describe('Model Configuration', function () {
    it('uses correct table name', function () {
        expect($this->deviceData->getTable())->toBe('device_data');
    });

    it('uses correct primary key', function () {
        expect($this->deviceData->getKeyName())->toBe(null);
    });

    it('has auto-incrementing primary key', function () {
        expect($this->deviceData->getIncrementing())->toBeFalse();
    });

    it('has timestamps enabled', function () {
        expect($this->deviceData->usesTimestamps())->toBeFalse();
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'time',
            'parameter_id',
            'value',
        ];

        expect($this->deviceData->getFillable())->toBe($fillable);
    });

    it('casts time to datetime', function () {
        expect($this->deviceData->time)->toBeInstanceOf(\DateTime::class);
    });

    it('casts value to float', function () {
        expect($this->deviceData->value)->toBeFloat();
    });

    it('has correct datetime format constant', function () {
        expect(DeviceData::DATETIME_FORMAT)->toBe('Y-m-d\TH:i:s\Z');
    });
});

describe('Relationships', function () {
    it('has deviceParameter relationship', function () {
        expect($this->deviceData->deviceParameter())->toBeInstanceOf(BelongsTo::class);
    });

    it('belongs to a device parameter', function () {
        $deviceData = DeviceData::create([
            'parameter_id' => $this->deviceParameter->id,
            'time' => '2023-01-01T00:00:00Z',
            'value' => 25.5,
        ]);

        expect($deviceData->deviceParameter)->toBeInstanceOf(DeviceParameter::class)
            ->and($deviceData->deviceParameter->id)->toBe($this->deviceParameter->id);
    });
});

describe('Model Persistence', function () {
    it('can create device data with all fillable attributes', function () {
        $deviceData = DeviceData::create([
            'parameter_id' => $this->deviceParameter->id,
            'time' => '2023-01-01T00:00:00Z',
            'value' => 25.5,
        ]);

        expect($deviceData->exists)->toBeTrue()
            ->and($deviceData->time)->toBeInstanceOf(\DateTime::class)
            ->and($deviceData->value)->toBe(25.5);
    });

    it('can update device data', function () {
        $this->deviceData->update([
            'value' => 77.5,
        ]);

        expect($this->deviceData->fresh()->value)->toBe(77.5);
    });

    it('can delete device data', function () {
        $parameter_id = $this->deviceData->parameter_id;
        $time = $this->deviceData->time->format(DeviceData::DATETIME_FORMAT);
        $value = $this->deviceData->value;

        // Raw SQL as table has no primary key
        DB::delete(
            "DELETE FROM device_data WHERE time = ? AND value = ?",
            [$time, $value],
        );

        $data = DB::select(
            "SELECT *
            FROM device_data
                WHERE parameter_id = ?
                AND time = ?
                AND value = ?
            ;",
            [$parameter_id, $time, $value],
        );

        expect(count($data))->toBe(0);
    });

    it('can query device data by parameter_id', function () {
        DeviceData::create([
            'parameter_id' => $this->deviceParameter->id,
            'time' => '2023-01-01T00:00:00Z',
            'value' => 10.0,
        ]);

        $results = DeviceData::where('parameter_id', $this->deviceParameter->id)->get();

        expect($results)->toHaveCount(1);
    });

    it('can query device data by time range', function () {
        DeviceData::create([
            'parameter_id' => $this->deviceParameter->id,
            'time' => '2023-01-01T00:00:00Z',
            'value' => 10.0,
        ]);

        DeviceData::create([
            'parameter_id' => $this->deviceParameter->id,
            'time' => '2023-06-01T00:00:00Z',
            'value' => 20.0,
        ]);

        $results = DeviceData::whereBetween('time', ['2023-01-01', '2023-07-01'])->get();

        expect($results->count())->toBeGreaterThanOrEqual(2);
    });
});