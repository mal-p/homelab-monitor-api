<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;

    protected $table = 'devices';

    protected $with = ['deviceType'];

    public $timestamps = true;

    protected $fillable = [
        'type_id',
        'name',
        'serial_number',
        'mpan',
        'location',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected $primaryKey = 'id';

    public $incrementing = true;

    /**
     * Fetch the type for this device.
     */
    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class, 'type_id', 'id');
    }

    /**
     * Fetch all measured DeviceParameters for this device.
     */
    public function deviceParameters(): HasMany
    {
        return $this->hasMany(DeviceParameter::class, 'device_id', 'id');
    }

    /*
     * Local Scopes
     */
    #[Scope]
    protected function electricity(Builder $query): void
    {
        $query->whereRelation('deviceType', 'name', 'Electricity meter');
    }

    #[Scope]
    protected function bluetooth(Builder $query): void
    {
        $query->whereRelation('deviceType', 'name', 'Bluetooth sensor');
    }
}
