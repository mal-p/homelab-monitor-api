<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class DeviceParameter extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceParameterFactory> */
    use HasFactory;

    /* PostgreSQL "alarm_type" enum. */
    public const array ALARM_TYPES = ['none', 'low', 'high'];

    public const int MIN_BUCKET_SIZE_MINS = 5;
    public const int MAX_BUCKET_SIZE_MINS = 1440; // 24 * 60

    protected $table = 'device_parameters';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'name',
        'unit',
        'alarm_type',
        'alarm_trigger',
        'alarm_hysteresis',
        'alarm_active',
        'alarm_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'alarm_trigger' => 'float',
            'alarm_hysteresis' => 'float',
            'alarm_active' => 'boolean',
            'alarm_updated_at' => 'datetime',
        ];
    }

    protected $primaryKey = 'id';

    public $incrementing = true;

    /**
     * Fetch the device for this measured parameter.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }

    /**
     * Fetch all measurements for this DeviceParameter.
     */
    public function allData(): HasMany
    {
        return $this->hasMany(DeviceData::class, 'parameter_id', 'id');
    }

    /**
     * Fetch measurements in time buckets for this DeviceParameter.
     * Uses TimescaleDB time_bucket function.
     */
    public function bucketData(int $bucketWidthMinutes, \DateTime $startTime, \DateTime $endTime): array
    {
        $paramId = $this->id;
    
        if ($bucketWidthMinutes < self::MIN_BUCKET_SIZE_MINS || $bucketWidthMinutes > self::MAX_BUCKET_SIZE_MINS ) {
            throw new \InvalidArgumentException('Invalid bucket width.');
        }

        if ($startTime > $endTime) {
            throw new \InvalidArgumentException('Start date must come before end date.');
        }

        $start = $startTime->format(DeviceData::DATETIME_FORMAT);
        $end = $endTime->format(DeviceData::DATETIME_FORMAT);

        $logs = DB::select(
            "SELECT
                time_bucket('{$bucketWidthMinutes} minutes', time) AS bucket,
                COUNT(*)   AS count,
                MIN(value) AS min_value,
                MAX(value) AS max_value,
                AVG(value) AS avg_value
            FROM device_data
                WHERE parameter_id = ?
                AND time >= ?
                AND time <  ?
            GROUP BY bucket
            ORDER BY bucket ASC, avg_value DESC, max_value DESC, min_value DESC;",

            [$paramId, $start, $end]
        );

        return array_map(function ($log) {
            $logAsArray = (array) $log;

            return [
                'bucket_start' => $logAsArray['bucket'],
                'count' => intval($logAsArray['count']),
                'min_value' => floatval($logAsArray['min_value']),
                'max_value' => floatval($logAsArray['max_value']),
                'avg_value' => floatval($logAsArray['avg_value']),
            ];
        }, $logs);
    }

    /**
     * Fetch the latest reading and alarm state for an array of DeviceParameter ids.
     *
     * @param array $deviceParameterIds  Validated ids
     */
    public static function heartbeat(array $deviceParameterIds): array
    {
        $paramIds = array_values(array_map('intval', $deviceParameterIds));
        $paramIdsPgArray = '{' . implode(',', $paramIds) . '}';

        $resultRows = DB::select(
            "WITH latest_data AS (
                SELECT DISTINCT ON (dd.parameter_id)
                    dd.parameter_id,
                    dd.time,
                    dd.value
                FROM device_data AS dd
                WHERE dd.parameter_id = ANY (?::int[])
                ORDER BY dd.parameter_id, dd.time DESC
            )
            SELECT
                dp.id,
                dp.name,
                dp.unit,
                dp.alarm_type,
                dp.alarm_active,
                ld.time            AS latest_time,
                ld.value           AS latest_value
            FROM device_parameters AS dp
            LEFT JOIN latest_data  AS ld
                ON dp.id = ld.parameter_id
            WHERE dp.id = ANY (?::int[])
            ORDER BY dp.id ASC;",

            [$paramIdsPgArray, $paramIdsPgArray]
        );

        return array_map(function ($row) {
            $rowAsArray = (array) $row;

            $latestTime  = $rowAsArray['latest_time'] ?? null;
            $latestValue = $rowAsArray['latest_value'] ?? null;

            return [
                'device_parameter_id' => intval($rowAsArray['id']),
                'name' => $rowAsArray['name'],
                'unit' => $rowAsArray['unit'],
                'alarm_type' => $rowAsArray['alarm_type'],
                'alarm_active' => (bool) $rowAsArray['alarm_active'],
                'latest_time' => $latestTime,
                'latest_value' => $latestValue === null ? null : floatval($latestValue),
            ];
        }, $resultRows);
    }
}
