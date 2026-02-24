<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\{Response};
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDeviceTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'id' => ['required', 'integer', 'exists:pgsql.device_types,id'],
            'name' => ['required', 'string', 'min:3', 'max:255', "unique:device_types,name,{$id}"],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Additional validation.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $name = $validator->safe()->name;

            if (is_string($name) && $name !== '' && !preg_match('/^[a-zA-Z0-9\+\-\_\= ]+$/', $name)) {
                $validator->errors()->add(
                    'name',
                    "The name may only contain alphanumeric characters, spaces and + - _ =",
                );
            }
        });
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
