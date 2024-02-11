<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAppSettings extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->user() || $this->user()->status) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'common' => 'required|array',
            'common.timelines.show_public' => 'required|boolean',
            'common.timelines.show_network' => 'required|boolean',
            'common.timelines.hide_likes_shares' => 'required|boolean',
            'common.media.hide_public_behind_cw' => 'required|boolean',
            'common.media.always_show_cw' => 'required|boolean',
            'common.media.show_alt_text' => 'required|boolean',
            'common.appearance.links_use_in_app_browser' => 'required|boolean',
            'common.appearance.theme' => 'required|string|in:light,dark,system',
        ];
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'common' => array_merge(
                $this->input('common'),
                [
                    'timelines' => [
                        'show_public' => $this->toBoolean($this->input('common.timelines.show_public')),
                        'show_network' => $this->toBoolean($this->input('common.timelines.show_network')),
                        'hide_likes_shares' => $this->toBoolean($this->input('common.timelines.hide_likes_shares')),
                    ],

                    'media' => [
                        'hide_public_behind_cw' => $this->toBoolean($this->input('common.media.hide_public_behind_cw')),
                        'always_show_cw' => $this->toBoolean($this->input('common.media.always_show_cw')),
                        'show_alt_text' => $this->toBoolean($this->input('common.media.show_alt_text')),
                    ],

                    'appearance' => [
                        'links_use_in_app_browser' => $this->toBoolean($this->input('common.appearance.links_use_in_app_browser')),
                        'theme' => $this->input('common.appearance.theme'),
                    ],
                ]
            ),
        ]);
    }

    /**
     * Convert to boolean
     *
     * @return bool
     */
    private function toBoolean($booleable)
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
