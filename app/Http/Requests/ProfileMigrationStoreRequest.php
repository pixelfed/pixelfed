<?php

namespace App\Http\Requests;

use App\Models\ProfileMigration;
use App\Services\FetchCacheService;
use App\Services\WebfingerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileMigrationStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ((bool) config_cache('federation.activitypub.enabled') === false ||
            (bool) config_cache('federation.migration') === false) {
            return false;
        }
        if (! $this->user() || $this->user()->status) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'acct' => 'required|email',
            'password' => 'required|current_password',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $err = $this->validateNewAccount();
                if ($err !== 'noerr') {
                    $validator->errors()->add(
                        'acct',
                        $err
                    );
                }
            },
        ];
    }

    protected function validateNewAccount()
    {
        if (ProfileMigration::whereProfileId($this->user()->profile_id)->where('created_at', '>', now()->subDays(30))->exists()) {
            return 'Error - You have migrated your account in the past 30 days, you can only perform a migration once per 30 days.';
        }
        $acct = WebfingerService::rawGet($this->acct);
        if (! $acct) {
            return 'The new account you provided is not responding to our requests.';
        }
        $pr = FetchCacheService::getJson($acct);
        if (! $pr || ! isset($pr['alsoKnownAs'])) {
            return 'Invalid account lookup response.';
        }
        if (! count($pr['alsoKnownAs']) || ! is_array($pr['alsoKnownAs'])) {
            return 'The new account does not contain an alias to your current account.';
        }
        $curAcctUrl = $this->user()->profile->permalink();
        if (! in_array($curAcctUrl, $pr['alsoKnownAs'])) {
            return 'The new account does not contain an alias to your current account.';
        }

        return 'noerr';
    }
}
