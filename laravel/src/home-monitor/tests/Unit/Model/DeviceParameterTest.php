<?php

use App\Models\{Device, DeviceData, DeviceParameter};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

beforeEach(function () {
    $this->deviceParameter = DeviceParameter::factory()->create();
});

describe('Model Configuration', function () {
    it('uses correct table name', function () {
        expect($this->deviceParameter->getTable())->toBe('device_parameters');
    });

    it('uses correct primary key', function () {
        expect($this->deviceParameter->getKeyName())->toBe('id');
    });

    it('has auto-incrementing primary key', function () {
        expect($this->deviceParameter->getIncrementing())->toBeTrue();
    });

    it('has timestamps disabled', function () {
        expect($this->deviceParameter->usesTimestamps())->toBeFalse();
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'device_id',
            'name',
            'unit',
            'alarm_type',
            'alarm_trigger',
            'alarm_hysteresis',
            'alarm_active',
            'alarm_updated_at',
        ];

        expect($this->deviceParameter->getFillable())->toBe($fillable);
    });

    it('casts alarm_trigger to float', function () {
        expect($this->deviceParameter->alarm_trigger)->toBeFloat();
    });

    it('casts alarm_hysteresis to float', function () {
        expect($this->deviceParameter->alarm_hysteresis)->toBe(0.0)->toBeFloat();
    });

    it('casts alarm_active to boolean', function () {
        expect($this->deviceParameter->alarm_active)->toBeFalse()->toBeBool();
    });

    it('casts alarm_updated_at to datetime', function () {
        expect($this->deviceParameter->alarm_updated_at)->toBeInstanceOf(\DateTime::class);
    });

    it('has alarm types enum', function () {
        expect(DeviceParameter::ALARM_TYPES)->toBe(['none', 'low', 'high']);

    });
});

describe('Relationships', function () {
    it('has device relationship', function () {
        expect($this->deviceParameter->device())->toBeInstanceOf(BelongsTo::class);
    });

    it('belongs to a device', function () {
        $device = Device::factory()->create();
        $deviceParam = DeviceParameter::factory()->create(['device_id' => $device->id]);

        expect($deviceParam->device)->toBeInstanceOf(Device::class)
            ->and($deviceParam->device->id)->toBe($device->id);
    });

    it('has all data relationship', function () {
        expect($this->deviceParameter->allData())->toBeInstanceOf(HasMany::class);
    });

    it('has many device data', function () {
        $deviceData = DeviceData::factory()->count(5)->create([
            'parameter_id' => $this->deviceParameter->id,
        ]);

        expect($this->deviceParameter->allData)->toHaveCount(5)
            ->and($this->deviceParameter->allData->first())->toBeInstanceOf(DeviceData::class);
    });
});

describe('Model Persistence', function () {
    it('can create parameter with all fillable attributes', function () {
        $device = Device::factory()->create();

        $deviceParam = DeviceParameter::create([
            'device_id' => $device->id,
            'name' => 'Test Parameter',
            'unit' => 'Test Unit',
            'alarm_type' => 'none',
            'alarm_trigger' => 10.5,
            'alarm_hysteresis' => 0.0,
            'alarm_active' => false,
            'alarm_updated_at' => now(),
        ]);

        expect($deviceParam->exists)->toBeTrue()
            ->and($deviceParam->name)->toBe('Test Parameter')
            ->and($deviceParam->unit)->toBe('Test Unit')
            ->and($deviceParam->alarm_type)->toBe('none')
            ->and($deviceParam->alarm_trigger)->toBe(10.5)
            ->and($deviceParam->alarm_hysteresis)->toBe(0.0)
            ->and($deviceParam->alarm_active)->toBeFalse()
            ->and($deviceParam->alarm_updated_at)->toBeInstanceOf(\DateTime::class);
    });

    it('can update device parameter attributes', function () {
        $this->deviceParameter->update([
            'name' => 'Updated Parameter',
            'alarm_type' => 'low',
            'alarm_active' => true,
        ]);

        expect($this->deviceParameter->fresh()->name)->toBe('Updated Parameter')
            ->and($this->deviceParameter->fresh()->alarm_type)->toBe('low')
            ->and($this->deviceParameter->fresh()->alarm_active)->toBeTrue();
    });

    it('can delete device parameter', function () {
        $id = $this->deviceParameter->id;
        $this->deviceParameter->delete();

        expect(DeviceParameter::find($id))->toBeNull();
    });
});