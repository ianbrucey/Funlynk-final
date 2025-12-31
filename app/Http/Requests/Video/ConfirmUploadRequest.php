<?php

namespace App\Http\Requests\Video;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'upload_token' => ['required', 'string', 'size:64'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'upload_token.required' => 'Upload token is required.',
            'upload_token.size' => 'Invalid upload token format.',
        ];
    }
}
