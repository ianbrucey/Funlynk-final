<?php

namespace App\Http\Requests\Video;

use Illuminate\Foundation\Http\FormRequest;

class RequestUploadUrlRequest extends FormRequest
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
        $maxFileSize = config('video.upload.max_file_size');
        $maxDuration = config('video.upload.max_duration');
        $allowedMimes = implode(',', config('video.upload.allowed_mimes'));

        return [
            'videoable_type' => ['required', 'string', 'in:post,activity,user,group'],
            'videoable_id' => ['required', 'uuid'],
            'filename' => ['required', 'string', 'max:255'],
            'file_size' => ['required', 'integer', 'min:1', "max:{$maxFileSize}"],
            'mime_type' => ['required', 'string', "in:{$allowedMimes}"],
            'duration_seconds' => ['nullable', 'integer', 'min:1', "max:{$maxDuration}"],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $maxFileSizeMb = round(config('video.upload.max_file_size') / 1024 / 1024);
        $maxDuration = config('video.upload.max_duration');

        return [
            'file_size.max' => "File size exceeds maximum of {$maxFileSizeMb}MB.",
            'duration_seconds.max' => "Video duration exceeds maximum of {$maxDuration} seconds.",
            'mime_type.in' => 'Invalid file type. Allowed: video/mp4, video/quicktime, video/webm.',
        ];
    }
}
