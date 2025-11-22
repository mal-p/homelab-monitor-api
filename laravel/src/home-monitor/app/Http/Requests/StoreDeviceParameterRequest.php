<?php

namespace App\Http\Requests;

use App\Models\DeviceParameter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\{Response};
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreDeviceParameterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Every authenticated user is authorized to perform all actions.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'integer', 'exists:pgsql.devices,id'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'alarm_type' => ['nullable', 'string', Rule::in(DeviceParameter::ALARM_TYPES)],
            'alarm_trigger' => ['nullable', 'numeric'],
            'alarm_hysteresis' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                ['errors' => $validator->errors()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            )
        );
    }
}
