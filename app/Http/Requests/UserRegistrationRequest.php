<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class UserRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|nullable|string',
            'password' => 'required|string|min:8',
            'injuries' => 'string|nullable',
            'allergies' => 'string|nullable',
            'medical_conditions' => 'string|nullable',
            'medications' => 'string|nullable',
            'gender' => 'string|nullable',
            'address' => 'string|nullable',
            'city' => 'string|nullable',
            'county' => 'string|nullable',
            'region' => 'string|nullable',
            'street' => 'string|nullable',
            'coach_id' => 'required|string|nullable',
            'emergency_contact_name' => 'string|nullable',
            'emergency_contact_phone' => 'string|nullable',
            'emergency_contact_email' => 'string|nullable',
            'emergency_notes' => 'string|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'The first name field is required.',
            'last_name.required' => 'The last name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'The email address has already been taken.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least :min characters long.',
            'coach_id.required' => 'Please provide the coach ID to continue',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            JsonResponse::create([
                'status' => 'error',
                'status_code' => 422,
                'errors' => $validator->errors(),
                'message' => 'One or more inputs have errors, please check that all required inputs are filled.'
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
