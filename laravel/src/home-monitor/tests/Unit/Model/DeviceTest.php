<?php

use App\Models\{Device, DeviceType, DeviceParameter};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

beforeEach(function () {
    $this->device = Device::factory()->create();
});

describe('Model Configuration', function () {
    it('uses correct table name', function () {
        expect($this->device->getTable())->toBe('devices');
    });

    it('uses correct primary key', function () {
        expect($this->device->getKeyName())->toBe('id');
    });

    it('has auto-incrementing primary key', function () {
        expect($this->device->getIncrementing())->toBeTrue();
    });

    it('has timestamps enabled', function () {
        expect($this->device->usesTimestamps())->toBeTrue();
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'type_id',
            'name',
            'serial_number',
            'mpan',
            'location',
            'description',
            'is_active',
        ];

        expect($this->device->getFillable())->toBe($fillable);
    });

    it('casts is_active to boolean', function () {
        $deviceType = DeviceType::factory()->create();

        $device = Device::factory()->create(['type_id' => $deviceType->id, 'is_active' => 1]);
        expect($device->is_active)->toBeTrue()->toBeBool();

        $device = Device::factory()->create(['type_id' => $deviceType->id, 'is_active' => 0]);
        expect($device->is_active)->toBeFalse()->toBeBool();
    });

    it('eager loads deviceType relationship by default', function () {
        $device = Device::first();
        expect($device->relationLoaded('deviceType'))->toBeTrue();
    });
});

describe('Relationships', function () {
    it('has deviceType relationship', function () {
        expect($this->device->deviceType())->toBeInstanceOf(BelongsTo::class);
    });

    it('belongs to a device type', function () {
        $deviceType = DeviceType::factory()->create();
        $device = Device::factory()->create(['type_id' => $deviceType->id]);

        expect($device->deviceType)->toBeInstanceOf(DeviceType::class)
            ->and($device->deviceType->id)->toBe($deviceType->id);
    });

    it('has deviceParameters relationship', function () {
        expect($this->device->deviceParameters())->toBeInstanceOf(HasMany::class);
    });

    it('has many device parameters', function () {
        $parameters = DeviceParameter::factory()->count(3)->create([
            'device_id' => $this->device->id,
        ]);

        expect($this->device->deviceParameters)->toHaveCount(3)
            ->and($this->device->deviceParameters->first())->toBeInstanceOf(DeviceParameter::class);
    });
});

describe('Local Scopes', function () {
    test('scopes filter sensor types correctly', function () {
        $electricityType = DeviceType::factory()->create(['name' => 'Electricity meter']);
        $bluetoothType = DeviceType::factory()->create(['name' => 'Bluetooth sensor']);

        $electricityDevice = Device::factory()->create(['type_id' => $electricityType->id]);
        $bluetoothDevice = Device::factory()->create(['type_id' => $bluetoothType->id]);

        $elecDevices = Device::electricity()->get();
        $btDevices = Device::bluetooth()->get();

        expect($elecDevices)->toHaveCount(1)
            ->and($elecDevices->first()->id)->toBe($electricityDevice->id);
        expect($btDevices)->toHaveCount(1)
            ->and($btDevices->first()->id)->toBe($bluetoothDevice->id);
    });

    test('scopes can be chained with other query methods', function () {
        $electricityType = DeviceType::factory()->create(['name' => 'Electricity meter']);

        Device::factory()->create(['type_id' => $electricityType->id, 'is_active' => true]);
        Device::factory()->create(['type_id' => $electricityType->id, 'is_active' => false]);

        $activeElectricity = Device::electricity()->where('is_active', true)->get();

        expect($activeElectricity)->toHaveCount(1)
            ->and($activeElectricity->first()->is_active)->toBeTrue();
    });
});

describe('Model Persistence', function () {
    it('can create device with all fillable attributes', function () {
        $deviceType = DeviceType::factory()->create();

        $device = Device::create([
            'type_id' => $deviceType->id,
            'name' => 'New Device',
            'serial_number' => 'SN12345',
            'mpan' => 'MPAN123',
            'location' => 'Test location',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        expect($device->exists)->toBeTrue()
            ->and($device->name)->toBe('New Device')
            ->and($device->serial_number)->toBe('SN12345')
            ->and($device->mpan)->toBe('MPAN123')
            ->and($device->location)->toBe('Test location')
            ->and($device->description)->toBe('Test description')
            ->and($device->is_active)->toBeTrue();
    });

    it('can update device attributes', function () {
        $this->device->update([
            'name' => 'Updated Device',
            'is_active' => false,
        ]);

        expect($this->device->fresh()->name)->toBe('Updated Device')
            ->and($this->device->fresh()->is_active)->toBeFalse();
    });

    it('can delete device', function () {
        $id = $this->device->id;
        $this->device->delete();

        expect(Device::find($id))->toBeNull();
    });
});