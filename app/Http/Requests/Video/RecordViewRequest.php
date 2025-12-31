<?php

namespace App\Http\Requests\Video;

use Illuminate\Foundation\Http\FormRequest;

class RecordViewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow anonymous views
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'watch_duration_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'completed' => ['nullable', 'boolean'],
            'referrer' => ['nullable', 'string', 'in:feed,profile,direct,search,group'],
        ];
    }
}
