<?php

namespace App\Jobs;

use App\Models\DeviceParameter;

use Aws\Sns\SnsClient;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAlarmNotification implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $deviceParameterId,
        public float $alarmTriggerValue,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $parameter = DeviceParameter::findOrFail($this->deviceParameterId);

            // Only send notifications for alarm ON events
            if ($parameter->alarm_active === false) {
                return;
            }

            $humanReadableTime = $parameter->alarm_updated_at->format('d M H:i:s A');

            $snsClient = new SnsClient([
                'region' => config('services.sns.region'),
                'version' => 'latest',
                'credentials' => [
                    'key' => config('services.sns.key'),
                    'secret' => config('services.sns.secret'),
                ],
            ]);

            $messageLines = [
                $parameter->name . ' on ' . $parameter->device->name,
                'Alarm ' . $parameter->alarm_active ? 'active' : 'inactive',
                'Trigger: ' . $this->alarmTriggerValue . $parameter->unit,
                'At: ' . $humanReadableTime,
            ];

            $snsClient->publish([
                'TopicArn' => config('services.sns.topic_arn'),
                'Message' => implode(".\n", $messageLines),
                'Subject' => "Alarm Triggered: {$parameter->name}",
            ]);

            Log::info('Alarm notification sent', [
                'parameter_id' => $this->deviceParameterId,
                'time' => $parameter->alarm_updated_at->format('Y-m-d\TH:i:s\Z'),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send alarm notification', [
                'parameter_id' => $this->deviceParameterId,
                'time' => $parameter->alarm_updated_at->format('Y-m-d\TH:i:s\Z'),
                'exception' => $e->getMessage(),
            ]);

            // fail job
            throw $e;
        }
    }
}
