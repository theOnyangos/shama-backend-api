<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nette\Utils\DateTime;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array|\JsonSerializable|\Illuminate\Contracts\Support\Arrayable
    {
        return [
            'id' => $this->id,
            'name' => $this->first_name.' '.$this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'member_id' => $this->member_id,
            'created_at' => (new DateTime($this->created_at))->format('Y-m-d H:i:s'),
        ];
    }

    public static function customValidationMessages(): array
    {
        return [
            'required' => 'Please provide the :attribute field to continue.',
            'email' => 'The :attribute must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'min' => 'The :attribute must be at least :min characters.',
            'string' => 'The :attribute must be a string.',
            'digits' => 'The :attribute must be exactly :digits digits. Example (07********)',
            'password.regex' => 'The :attribute must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one digit, and one special character.',
            'regex' => 'The :attribute format is invalid.',
            'admin_id.exists' => 'The selected admin is invalid.',
            'coach_id.exists' => 'The selected coach is invalid.',
        ];
    }

    // Validation fields for team registration
    public static function teamValidationFields(): array
    {
        return [
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|nullable|string|digits:10|unique:users,phone',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
            'injuries' => 'string|nullable',
            'allergies' => 'required|string|nullable',
            'medical_conditions' => 'required|string|nullable',
            'medications' => 'required|string|nullable',
            'gender' => 'required|string|nullable',
            'address' => 'string|nullable',
            'city' => 'string|nullable',
            'county' => 'string|nullable',
            'region' => 'string|nullable',
            'street' => 'string|nullable',
            'coach_id' => 'required|string|nullable',
            'emergency_contact_name' => 'required|string|nullable',
            'emergency_contact_phone' => 'required|string|nullable|digits:10',
            'emergency_contact_email' => 'required|string|nullable',
            'emergency_notes' => 'required|string|nullable',
            'school_level' => 'string|nullable',
            'school_address' => 'string|nullable',
            'school_city' => 'string|nullable',
            'school_phone' => 'string|nullable|digits:10',
            'school_email' => 'string|nullable',
            'school_grade' => 'string|nullable',
            'school_counselor_name' => 'string|nullable'
        ];
    }

    // Validate team update fields
    public static function teamValidationFieldsUpdate(): array
    {
        return [
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|nullable|string|digits:10',
            'injuries' => 'string|nullable',
            'allergies' => 'required|string|nullable',
            'medical_conditions' => 'required|string|nullable',
            'medications' => 'required|string|nullable',
            'gender' => 'required|string|nullable',
            'address' => 'string|nullable',
            'city' => 'string|nullable',
            'county' => 'string|nullable',
            'region' => 'string|nullable',
            'street' => 'string|nullable',
            'coach_id' => 'required|string|nullable',
            'emergency_contact_name' => 'required|string|nullable',
            'emergency_contact_phone' => 'required|string|nullable|digits:10',
            'emergency_contact_email' => 'required|string|nullable',
            'emergency_notes' => 'required|string|nullable',
            'school_level' => 'string|nullable',
            'school_address' => 'string|nullable',
            'school_city' => 'string|nullable',
            'school_phone' => 'string|nullable|digits:10',
            'school_email' => 'string|nullable',
            'school_grade' => 'string|nullable',
            'school_counselor_name' => 'string|nullable'
        ];
    }

    // Validation fields for staff registration
    public static function staffValidationFields(): array
    {
        return [
            'full_name' => 'required|string|regex:/^[A-Za-z]+( [A-Za-z]+)+$/',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|nullable|string|digits:10|unique:users,phone',
            'user_type' => 'required',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
        ];
    }

    public static function validateNewTeamFields(): array
    {
        return [
            'team_name' => 'required|string',
            'team_location' => 'required',
            'team_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string',
            'coaches' => 'required',
            'players' => 'required',
        ];
    }
}
