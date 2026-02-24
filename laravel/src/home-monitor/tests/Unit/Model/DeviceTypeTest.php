<?php

use App\Models\{Device, DeviceType};
use Illuminate\Database\Eloquent\Relations\HasMany;

beforeEach(function () {
    /** @var DeviceType $this->deviceType */
    $this->deviceType = DeviceType::factory()->create();
});

describe('Model Configuration', function () {
    it('uses correct table name', function () {
        expect($this->deviceType->getTable())->toBe('device_types');
    });

    it('uses correct primary key', function () {
        expect($this->deviceType->getKeyName())->toBe('id');
    });

    it('has auto-incrementing primary key', function () {
        expect($this->deviceType->getIncrementing())->toBeTrue();
    });

    it('has timestamps disabled', function () {
        expect($this->deviceType->usesTimestamps())->toBeFalse();
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'name',
            'description',
        ];

        expect($this->deviceType->getFillable())->toBe($fillable);
    });
});

describe('Relationships', function () {
    it('has devices relationship', function () {
        expect($this->deviceType->devices())->toBeInstanceOf(HasMany::class);
    });

    it('has many devices', function () {
        Device::factory()->count(3)->create([
            'type_id' => $this->deviceType->id,
        ]);

        $freshDeviceType = $this->deviceType->fresh();

        expect($freshDeviceType->devices)->toHaveCount(3)
            ->and($freshDeviceType->devices->first())->toBeInstanceOf(Device::class);
    });
});

describe('Model Persistence', function () {
    it('can create device type with all fillable attributes', function () {
        $deviceType = DeviceType::create([
            'name' => 'Test Device Type',
            'description' => 'Test description',
        ]);

        expect($deviceType->exists)->toBeTrue()
            ->and($deviceType->name)->toBe('Test Device Type')
            ->and($deviceType->description)->toBe('Test description');
    });

    it('can update device type attributes', function () {
        $this->deviceType->update([
            'name' => 'Updated Device Type',
            'description' => 'Updated description',
        ]);

        expect($this->deviceType->fresh()->name)->toBe('Updated Device Type')
            ->and($this->deviceType->fresh()->description)->toBe('Updated description');
    });

    it('can delete device type', function () {
        $id = $this->deviceType->id;
        $this->deviceType->delete();

        expect(DeviceType::find($id))->toBeNull();
    });
});
