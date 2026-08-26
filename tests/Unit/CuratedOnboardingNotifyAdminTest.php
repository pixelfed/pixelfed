<?php

namespace Tests\Unit;

use App\Jobs\CuratedOnboarding\CuratedOnboardingNotifyAdminNewApplicationPipeline;
use App\Mail\CuratedRegisterNotifyAdmin;
use App\Models\CuratedRegister;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CuratedOnboardingNotifyAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    protected function createCuratedRegister(): CuratedRegister
    {
        return CuratedRegister::create([
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'password' => bcrypt('password'),
            'ip_address' => '127.0.0.1',
            'verify_code' => 'testcode123',
            'reason_to_join' => 'I want to share photos',
            'email_verified_at' => now(),
            'user_has_responded' => false,
        ]);
    }

    #[Test]
    public function it_does_not_send_when_notification_is_disabled()
    {
        Config::set('instance.curated_registration.notify.admin.on_verify_email.enabled', false);

        $cr = $this->createCuratedRegister();
        $admin = User::factory()->admin()->create();

        $job = new CuratedOnboardingNotifyAdminNewApplicationPipeline($cr);
        $job->handle();

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_sends_to_all_admins_when_to_usernames_is_not_configured()
    {
        Config::set('instance.curated_registration.notify.admin.on_verify_email.enabled', true);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.bundle', false);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.to_usernames', null);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.cc_addresses', null);

        $cr = $this->createCuratedRegister();
        $admin1 = User::factory()->admin()->create(['email' => 'admin1@example.com']);
        $admin2 = User::factory()->admin()->create(['email' => 'admin2@example.com']);
        // Non-admin should not receive the email
        User::factory()->create(['email' => 'regular@example.com']);

        $job = new CuratedOnboardingNotifyAdminNewApplicationPipeline($cr);
        $job->handle();

        Mail::assertSent(CuratedRegisterNotifyAdmin::class, 2);
        Mail::assertSent(CuratedRegisterNotifyAdmin::class, function ($mail) {
            return $mail->hasTo('admin1@example.com');
        });
        Mail::assertSent(CuratedRegisterNotifyAdmin::class, function ($mail) {
            return $mail->hasTo('admin2@example.com');
        });
    }

    #[Test]
    public function it_sends_only_to_specified_admin_usernames_when_configured()
    {
        Config::set('instance.curated_registration.notify.admin.on_verify_email.enabled', true);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.bundle', false);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.cc_addresses', null);

        $cr = $this->createCuratedRegister();
        $admin1 = User::factory()->admin()->create(['username' => 'targetadmin', 'email' => 'target@example.com']);
        $admin2 = User::factory()->admin()->create(['username' => 'otheradmin', 'email' => 'other@example.com']);

        Config::set('instance.curated_registration.notify.admin.on_verify_email.to_usernames', 'targetadmin');

        $job = new CuratedOnboardingNotifyAdminNewApplicationPipeline($cr);
        $job->handle();

        Mail::assertSent(CuratedRegisterNotifyAdmin::class, 1);
        Mail::assertSent(CuratedRegisterNotifyAdmin::class, function ($mail) {
            return $mail->hasTo('target@example.com');
        });
    }

    #[Test]
    public function it_sends_nothing_when_no_admin_users_exist()
    {
        Config::set('instance.curated_registration.notify.admin.on_verify_email.enabled', true);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.bundle', false);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.to_usernames', null);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.cc_addresses', null);

        $cr = $this->createCuratedRegister();
        // Only non-admin users
        User::factory()->create();

        $job = new CuratedOnboardingNotifyAdminNewApplicationPipeline($cr);
        $job->handle();

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_includes_cc_addresses_when_configured()
    {
        Config::set('instance.curated_registration.notify.admin.on_verify_email.enabled', true);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.bundle', false);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.to_usernames', null);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.cc_addresses', 'cc1@example.com, cc2@example.com');

        $cr = $this->createCuratedRegister();
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $job = new CuratedOnboardingNotifyAdminNewApplicationPipeline($cr);
        $job->handle();

        // Admin gets their own email
        Mail::assertSent(CuratedRegisterNotifyAdmin::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
        // CC addresses get a separate email
        Mail::assertSent(CuratedRegisterNotifyAdmin::class, function ($mail) {
            return $mail->hasTo('cc1@example.com') && $mail->hasTo('cc2@example.com');
        });
    }

    #[Test]
    public function it_sends_to_multiple_specified_admin_usernames()
    {
        Config::set('instance.curated_registration.notify.admin.on_verify_email.enabled', true);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.bundle', false);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.cc_addresses', null);
        Config::set('instance.curated_registration.notify.admin.on_verify_email.to_usernames', 'admin1,admin2');

        $cr = $this->createCuratedRegister();
        $a1 = User::factory()->admin()->create(['username' => 'admin1', 'email' => 'a1@example.com']);
        $a2 = User::factory()->admin()->create(['username' => 'admin2', 'email' => 'a2@example.com']);
        $a3 = User::factory()->admin()->create(['username' => 'admin3', 'email' => 'a3@example.com']);

        $job = new CuratedOnboardingNotifyAdminNewApplicationPipeline($cr);
        $job->handle();

        Mail::assertSent(CuratedRegisterNotifyAdmin::class, 2);
        Mail::assertSent(CuratedRegisterNotifyAdmin::class, function ($mail) {
            return $mail->hasTo('a1@example.com');
        });
        Mail::assertSent(CuratedRegisterNotifyAdmin::class, function ($mail) {
            return $mail->hasTo('a2@example.com');
        });
    }
}
