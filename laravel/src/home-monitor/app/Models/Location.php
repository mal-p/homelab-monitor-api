<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{HasMany, HasManyThrough};

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory;

    protected $table = 'locations';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $primaryKey = 'id';

    public $incrementing = true;

    /**
     * Fetch all Devices assigned to this location.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'location_id', 'id');
    }

    /**
     * Fetch all DeviceParameters for devices in this location.
     */
    public function deviceParameters(): HasManyThrough
    {
        return $this->hasManyThrough(
            DeviceParameter::class,
            Device::class,
            'location_id',
            'device_id',
            'id',
            'id',
        );
    }
}
