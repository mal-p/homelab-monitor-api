<?php

namespace App\Console\Commands;

use App\Models\{Device, DeviceData, DeviceParameter};

use Illuminate\Support\Facades\{Http, Log};

class FetchOctopusDeviceData extends FetchExternalDeviceData
{
    private const string OCTOPUS_API_BASE_URL = 'https://api.octopus.energy/v1';
    private const string OCTOPUS_API_DATE_FORMAT = 'Y-m-d\TH:i\Z'; // 2023-01-01T12:00Z

    /* Data available to regular customers lags by up to 48 hours. Often updated around midnight.
     * Default to the most recent 24 hours of data available.
     */
    private const int DEFAULT_LOOKBACK_HOURS = 72;
    private const int MAX_QUERY_RESULTS = 100; // arbitrary maximum which we assert is greater than needed

    public const array VALID_AGGREGATE_BUCKET_SIZES = ['hour', 'day', 'week', 'month', 'quarter'];

    /**
     * The name and signature of the console command.
     * Test with: `php artisan device:fetch-octopus-data --parameter-id=1`
     *
     * @var string
     */
    protected $signature = 'device:fetch-octopus-data {--parameter-id= : Specific parameter ID to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data from Octopus API and store it for device parameters';


    protected function fetchRecentForParameter(
        Device $device,
        DeviceParameter $parameter,
        int|null $lookbackHours = null,
        int|string|null $bucketSize = null,
    ): array
    {
        if (is_null($lookbackHours) || $lookbackHours <= 0) {
            $lookbackHours = self::DEFAULT_LOOKBACK_HOURS;
        }
        if (is_null($bucketSize) || !in_array($bucketSize, self::VALID_AGGREGATE_BUCKET_SIZES)) {
            $bucketSize = self::VALID_AGGREGATE_BUCKET_SIZES[0];
        }
        $serialNumber = $device->serial_number;
        $mpan = $device->mpan;
        $apiKey = config('services.octopus.api_key');

        /* Documentation see:
         * https://developer.octopus.energy/rest/reference/#tag/electricity-meter-points/operation/List%20consumption%20for%20an%20electricity%20meter
         */
        $baseUrl = self::OCTOPUS_API_BASE_URL;
        $uri = "{$baseUrl}/electricity-meter-points/{$mpan}/meters/{$serialNumber}/consumption/";

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $fetchEnd = $now->setTime($now->format('H'), 0, 0); // beginning of the current hour
        $fetchStart = $fetchEnd->sub(new \DateInterval("PT{$lookbackHours}H"));

        try {
            $response = Http::retry(2, 400)
                ->withBasicAuth($apiKey, '')
                ->beforeSending(function ($request, $options) {
                    Log::debug('Full Octopus URI: ' . $request->url());
                })
                ->get($uri, [
                    'page_size' => self::MAX_QUERY_RESULTS,
                    'period_from' => $fetchStart->format(self::OCTOPUS_API_DATE_FORMAT),
                    'period_to' => $fetchEnd->format(self::OCTOPUS_API_DATE_FORMAT),
                    'group_by' => $bucketSize,
                    'order_by' => 'period', // order by datetime ASC
                ]);

            if (!$response->successful()) {
                Log::error("Failed to fetch data for parameter {$parameter->id} from external API");
                return ['data' => []];
            }

            $data = $response->json();

            /* Returned data format:
             * {
             *     "count": 10,
             *     "next": null,
             *     "previous": null,
             *     "results": [
             *         {
             *             "consumption": 0.236,
             *             "interval_start": "2023-10-02T01:00:00+01:00",
             *             "interval_end": "2023-10-02T03:00:00+01:00"
             *         },
             *         ...
             *     ]
             * }
            */

            ['count' => $numReadings, 'next' => $nextPage, "results" => $readings] = $data;

            Log::info("Fetched {$numReadings} readings from Octopus API");

            if (!is_null($nextPage)) {
                $start = $fetchStart->format(self::OCTOPUS_API_DATE_FORMAT);
                $end = $fetchEnd->format(self::OCTOPUS_API_DATE_FORMAT);
                $maxResults = self::MAX_QUERY_RESULTS;

                Log::warning("Missing data. Interval [{$start} - {$end}] with bucket size {$bucketSize} contains more than max allowed readings ({$maxResults})");
            }

            $utcTimeZone = new \DateTimeZone('UTC');
            $formattedData = [
                'data' => collect($readings ?? [])->map(function($reading) use ($utcTimeZone) {
                    $utcTime = (new \DateTime($reading['interval_start']))->setTimezone($utcTimeZone);

                    return [
                        'value' => $reading['consumption'],
                        'time' => $utcTime->format(DeviceData::DATETIME_FORMAT),
                    ];
                })->toArray()
            ];

            return $formattedData;

        } catch (\Exception $e) {
            Log::error("Error fetching data for parameter {$parameter->id}: " . $e->getMessage());
            $this->error("Failed to fetch data for parameter {$parameter->id}");

            return ['data' => []];
        }
    }
}
