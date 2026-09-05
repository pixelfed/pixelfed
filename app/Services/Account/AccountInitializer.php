<?php

namespace App\Services\Account;

use App\Jobs\AvatarPipeline\CreateAvatar;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;

class AccountInitializer
{
    public function initialize(User $user): Profile
    {
        $profile = $this->ensureProfile($user);

        DB::transaction(function () use ($user, $profile) {
            UserSetting::firstOrCreate([
                'user_id' => $user->id,
            ]);

            if ((int) $user->profile_id !== (int) $profile->id) {
                $user->profile_id = $profile->id;
                $user->saveQuietly();
            }
        });

        $user->setRelation('profile', $profile);
        $user->unsetRelation('settings');

        return $profile;
    }

    protected function ensureProfile(User $user): Profile
    {
        $profile = $user->profile()->withTrashed()->first();

        if ($profile) {
            if ($profile->trashed() && $user->created_at && $user->created_at->lt(now()->subDay()) && empty($user->status)) {
                $profile->restore();
            }

            return $profile;
        }

        return DB::transaction(function () use ($user) {
            $profile = Profile::withTrashed()->whereUserId($user->id)->first();

            if ($profile) {
                if ($profile->trashed() && $user->created_at && $user->created_at->lt(now()->subDay()) && empty($user->status)) {
                    $profile->restore();
                }

                return $profile;
            }

            $profile = Profile::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
            ]);

            $pki = openssl_pkey_new([
                'digest_alg' => 'sha512',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            if ($pki === false || ! openssl_pkey_export($pki, $privateKey)) {
                throw new \RuntimeException('Unable to generate the profile RSA keypair.');
            }

            $details = openssl_pkey_get_details($pki);

            if (! is_array($details) || empty($details['key'])) {
                throw new \RuntimeException('Unable to read the profile RSA public key.');
            }

            $profile->private_key = $privateKey;
            $profile->public_key = $details['key'];
            $profile->save();

            CreateAvatar::dispatch($profile);

            return $profile;
        });
    }
}
