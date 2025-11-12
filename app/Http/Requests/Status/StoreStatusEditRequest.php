<?php

namespace App\Http\Requests\Status;

use App\Media;
use App\Status;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreStatusEditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $profile = $this->user()->profile;
        if ($profile->status != null) {
            return false;
        }
        if ($profile->unlisted == true && $profile->cw == true) {
            return false;
        }
        $types = [
            'photo',
            'photo:album',
            'photo:video:album',
            'reply',
            'text',
            'video',
            'video:album',
        ];
        $scopes = ['public', 'unlisted', 'private'];
        $status = Status::whereNull('reblog_of_id')->whereIn('type', $types)->whereIn('scope', $scopes)->find($this->route('id'));

        return $status && $this->user()->profile_id === $status->profile_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|max:'.config_cache('pixelfed.max_caption_length'),
            'spoiler_text' => 'nullable|string|max:140',
            'sensitive' => 'sometimes|boolean',
            'media_ids' => [
                'nullable',
                'required_without:status',
                'array',
                'max:'.(int) config_cache('pixelfed.max_album_length'),
                function (string $attribute, mixed $value, Closure $fail) {
                    Media::whereProfileId($this->user()->profile_id)
                        ->where(function ($query) {
                            return $query->whereNull('status_id')
                                ->orWhere('status_id', '=', $this->route('id'));
                        })
                        ->findOrFail($value);
                },
            ],
            'location' => 'sometimes|nullable',
            'location.id' => 'sometimes|integer|min:1|max:128769',
            'location.country' => 'required_with:location.id',
            'location.name' => 'required_with:location.id',
        ];
    }
}
