<?php

namespace App\Console\Commands;

use App\Models\{Device, DeviceParameter};
use \App\Http\Controllers\DeviceDataController;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

abstract class FetchExternalDeviceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:fetch-external-data {--parameter-id= : Specific parameter ID to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data from external API and store it for device parameters';

    /**
     * Execute the console command.
     * Test with: `php artisan device:fetch-external-data --parameter-id=1`
     */
    public function handle()
    {
        $parameterId = $this->option('parameter-id');

        try {

            $parameters = DeviceParameter::with('device')->whereHas('device', function($query) {
                $query->where('is_active', true);
            });
            
            if ($parameterId) {
                $parameters = $parameters->where('id', $parameterId);
            
            } else {
                // DeviceType is eager loaded with Device
                $parameters = $parameters->whereHas('device.deviceType', function($query) {
                    $query->where('name', 'Electricity meter');
                });
            }

            $parameterRecords = $parameters->get();
            if ($parameterRecords->count() === 0) {
                throw new ModelNotFoundException('No matching DeviceParameters found');
            }

            foreach ($parameterRecords as $parameter) {
                $formattedData = $this->fetchRecentForParameter(
                    $parameter->device,
                    $parameter,
                    null, // default lookback window
                    null, // default bucket size
                );

                $this->storeForParameter($parameter->id, $formattedData);

                $this->info("External data inserted for parameter with ID {$parameter->id}");
            }

        } catch (ModelNotFoundException $e) {
            Log::error("No active DeviceParameters found (matching ID {$parameterId})");
            $this->error("No active DeviceParameters found (matching ID {$parameterId})");

            return Command::FAILURE;

        } catch (\Exception $e) {
            Log::error("Error fetching data: " . $e->getMessage());
            $this->error('Failed to fetch data');

            return Command::FAILURE;
        }

        $this->info('External data fetch completed');
        return Command::SUCCESS;
    }

    /**
     * Store formatted data for a given parameter.
     *
     * @var int   $parameterId    The device parameter ID.
     * @var array $formattedData  Data array formatted for DeviceData::store() method.
     *                            ```
     *                            [
     *                                'data' => [
     *                                    ['value' => 42.8, 'time' => '2023-01-01T13:00:00Z'],
     *                                ]
     *                            ]
     *                            ```
     */
    protected function storeForParameter(int $parameterId, array $formattedData): void
    {
        $numDataPoints = count($formattedData);

        if ($numDataPoints > 0) {
            /* Store using existing endpoint logic.
             * We call the controller (store) method directly, instead of making an HTTP request.
             */ 
            $controller = app(DeviceDataController::class);
            $request = request()->merge($formattedData);
    
            $response = $controller->store($request, $parameterId);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== Response::HTTP_CREATED) {
                $content = json_decode($response->getContent(), true);

                $this->error("Failed to store data for parameter {$parameterId}. Status: {$statusCode}");
                if (isset($content['errors'])) {
                    $this->error("Errors: " . json_encode($content['errors']));
                }

                throw new \Exception('Failed to store data for parameter');
            }
        }
    }

    /**
     * Fetch recent data for the given parameter from an external API.
     *
     * @return array Shape to match that expected by storeForParamter()
     */
    abstract protected function fetchRecentForParameter(
        Device $device,
        DeviceParameter $parameter,
        int|null $lookbackHours = null,
        int|string|null $bucketSize = null,
    ): array;

}
