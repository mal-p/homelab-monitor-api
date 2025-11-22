<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceTypeFactory> */
    use HasFactory;

    protected $table = 'device_types';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $primaryKey = 'id';

    public $incrementing = true;

    /**
     * Fetch all Devices with a given type.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'type_id', 'id');
    }
}
