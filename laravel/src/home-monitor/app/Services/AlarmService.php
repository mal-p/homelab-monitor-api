<?php

namespace App\Services;

use App\Models\{DeviceData, DeviceParameter};

class AlarmService
{
    /**
     * Loop through an array of sorted, unique time-series data for the given parameter.
     * Check for any alarm state changes at each step.
     * Return only the final alarm state and whether or not it was changed.
     */
    public function processAlarmChanges(DeviceParameter $deviceParam, array $uniqueSortedData): array
    {
        // All these fields are contstrained to be non-null by DB
        $alarmType = $deviceParam->alarm_type;
        $alarmTrigger = $deviceParam->alarm_trigger;
        $alarmHyst = $deviceParam->alarm_hysteresis;
        $alarmActive = $deviceParam->alarm_active;
        $alarmUpdatedAt = $deviceParam->alarm_updated_at ?? null; // cast to DateTime by Model

        $alarmState = [
            'stateChanged' => false,
            'finalAlarmActive' => $alarmActive,
            'finalParamValue' => null,
            'finalUpdateTime' => null,
        ];
        // For alarm type 'none' an alarm can never become active. Return early.
        if ($alarmType === 'none' && $alarmActive === false) {
            return $alarmState;
        }

        // Track the current alarm state as we iterate
        $currentAlarmActiveState = $alarmActive;

        foreach ($uniqueSortedData as $reading) {
            ['time' => $time, 'value' => $value] = $reading;

            $dt = \DateTime::createFromFormat(DeviceData::DATETIME_FORMAT, $time);

            // Skip if data is older than last alarm state change
            if (($dt === false) || ($alarmUpdatedAt && $dt <= $alarmUpdatedAt)) {
                continue;
            }

            $stateChanged = $this->checkAlarmStateChange(
                $alarmType,
                $currentAlarmActiveState,
                $value,
                $alarmTrigger,
                $alarmHyst
            );

            if ($stateChanged) {
                // For alarm type 'none', any change must be to OFF state
                $currentAlarmActiveState = ($alarmType === 'none') ? false : !$currentAlarmActiveState;
                $alarmState['stateChanged'] = true;
                $alarmState['finalAlarmActive'] = $currentAlarmActiveState;
                $alarmState['finalParamValue'] = $value;
                $alarmState['finalUpdateTime'] = $time;
            }
        }

        return $alarmState;
    }

    /**
     * Determine whether a given value should cause an alarm to change state.
     * Implements symmetric (value-based) hysteresis either side of alarm trigger value.
     */
    private function checkAlarmStateChange(
        string $type,
        bool $currentStateIsActive,
        float $value,
        ?float $trigger,
        ?float $hysteresis
    ): bool {
        if ($trigger === null || $hysteresis === null) {
            return false;
        }

        $alarmStateChanged = false;

        switch ($type) {
            case 'low':
                $alarmChangeThreshold = $currentStateIsActive
                    ? ($trigger + $hysteresis)
                    : ($trigger - $hysteresis);

                $alarmStateChanged = $currentStateIsActive
                    ? ($value > $alarmChangeThreshold) 
                    : ($value <= $alarmChangeThreshold);

                break;

            case 'high':
                $alarmChangeThreshold = $currentStateIsActive
                    ? ($trigger - $hysteresis)
                    : ($trigger + $hysteresis);

                $alarmStateChanged = $currentStateIsActive
                    ? ($value < $alarmChangeThreshold)
                    : ($value >= $alarmChangeThreshold);

                break;

            case 'none':
                // If the alarm type is changed to 'none' while the alarm active, we must set activity false.
                $alarmStateChanged = $currentStateIsActive !== false;
                break;

            default:
                // All DB param_alarm_type enum cases handled
        }

        return $alarmStateChanged;
    }
}
