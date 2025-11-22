<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use ThiagoPrz\CompositeKey\HasCompositeKey;

/**
 * Time-series data hypertable.
 */
class DeviceData extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceDataFactory> */
    use HasFactory;
    // use HasCompositeKey;

    public const string DATETIME_FORMAT = 'Y-m-d\TH:i:s\Z'; // 2023-01-01T12:00:00Z

    protected $table = 'device_data';

    public $timestamps = false;

    protected $fillable = [
        'time',
        'parameter_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'time' => 'datetime',
            'value' => 'float',
        ];
    }

    /* Composite PK
     * Attempts to use the following package were unsuccessful:
     *   https://packagist.org/packages/thiagoprz/eloquent-composite-key
     *
     *   $oneDataLog = DeviceData::find([
     *       'parameter_id' => 1,
     *       'time' => '2023-01-01T00:00:00Z',
     *   ]);
     */
    // protected $primaryKey = ['parameter_id', 'time'];

    protected $primaryKey = null;
    public $incrementing = false;

    /**
     * Fetch the DeviceParameter for this data point.
     */
    public function deviceParameter(): BelongsTo
    {
        return $this->belongsTo(DeviceParameter::class, 'parameter_id', 'id');
    }
}
