<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadEmailSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only allow authenticated users with admin permissions
        return $this->user() && $this->user()->can('update', \VentureDrake\LaravelCrm\Models\Setting::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email_subject' => 'required|string|max:255',
            'email_content' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email_subject.required' => 'Email subject is required.',
            'email_subject.max' => 'Email subject cannot exceed 255 characters.',
            'email_content.required' => 'Email content is required.',
        ];
    }
}
