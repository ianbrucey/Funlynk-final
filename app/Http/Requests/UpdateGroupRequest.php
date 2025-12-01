<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Assuming the route model binding for group is 'group'
        $groupId = $this->route('group') ? $this->route('group')->id : null;

        return [
            'name' => 'sometimes|string|max:100|unique:groups,name,'.$groupId,
            'description' => 'nullable|string|max:1000',
            'avatar_url' => 'nullable|url|max:255',
            'cover_image_url' => 'nullable|url|max:255',
            'privacy' => 'sometimes|in:public,private',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }
}
