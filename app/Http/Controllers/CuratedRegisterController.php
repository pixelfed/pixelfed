<?php

namespace App\Http\Controllers;

use App\Jobs\CuratedOnboarding\CuratedOnboardingNotifyAdminNewApplicationPipeline;
use App\Mail\CuratedRegisterConfirmEmail;
use App\Models\CuratedRegister;
use App\Models\CuratedRegisterActivity;
use App\Services\EmailService;
use App\Util\Lexer\RestrictedNames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CuratedRegisterController extends Controller
{
    public function preCheck($allowWhenDisabled = false)
    {
        if (! $allowWhenDisabled) {
            abort_unless((bool) config_cache('instance.curated_registration.enabled'), 404);

            if ((bool) config_cache('pixelfed.open_registration')) {
                abort_if(config('instance.curated_registration.state.only_enabled_on_closed_reg'), 404);
            } else {
                abort_unless(config('instance.curated_registration.state.fallback_on_closed_reg'), 404);
            }
        } else {
            abort_unless(config('instance.curated_registration.state.fallback_on_closed_reg'), 404);
        }
    }

    public function index(Request $request)
    {
        abort_if($request->user(), 404);

        return view('auth.curated-register.index', ['step' => 1]);
    }

    public function concierge(Request $request)
    {
        abort_if($request->user(), 404);
        $this->preCheck(true);
        $emailConfirmed = $request->session()->has('cur-reg-con.email-confirmed') &&
            $request->has('next') &&
            $request->session()->has('cur-reg-con.cr-id');

        return view('auth.curated-register.concierge', compact('emailConfirmed'));
    }

    public function conciergeResponseSent(Request $request)
    {
        $this->preCheck(true);

        return view('auth.curated-register.user_response_sent');
    }

    public function conciergeFormShow(Request $request)
    {
        abort_if($request->user(), 404);
        $this->preCheck(true);
        abort_unless(
            $request->session()->has('cur-reg-con.email-confirmed') &&
            $request->session()->has('cur-reg-con.cr-id') &&
            $request->session()->has('cur-reg-con.ac-id'), 404);
        $crid = $request->session()->get('cur-reg-con.cr-id');
        $arid = $request->session()->get('cur-reg-con.ac-id');
        $showCaptcha = config('instance.curated_registration.captcha_enabled');
        if ($attempts = $request->session()->get('cur-reg-con-attempt')) {
            $showCaptcha = $attempts && $attempts >= 2;
        } else {
            $showCaptcha = false;
        }
        $activity = CuratedRegisterActivity::whereRegisterId($crid)->whereFromAdmin(true)->findOrFail($arid);

        return view('auth.curated-register.concierge_form', compact('activity', 'showCaptcha'));
    }

    public function conciergeFormStore(Request $request)
    {
        abort_if($request->user(), 404);
        $this->preCheck(true);
        $request->session()->increment('cur-reg-con-attempt');
        abort_unless(
            $request->session()->has('cur-reg-con.email-confirmed') &&
            $request->session()->has('cur-reg-con.cr-id') &&
            $request->session()->has('cur-reg-con.ac-id'), 404);
        $attempts = $request->session()->get('cur-reg-con-attempt');
        $messages = [];
        $rules = [
            'response' => 'required|string|min:5|max:1000',
            'crid' => 'required|integer|min:1',
            'acid' => 'required|integer|min:1',
        ];
        if (config('instance.curated_registration.captcha_enabled') && $attempts >= 3) {
            $rules['h-captcha-response'] = 'required|captcha';
            $messages['h-captcha-response.required'] = 'The captcha must be filled';
        }
        $this->validate($request, $rules, $messages);
        $crid = $request->session()->get('cur-reg-con.cr-id');
        $acid = $request->session()->get('cur-reg-con.ac-id');
        abort_if((string) $crid !== $request->input('crid'), 404);
        abort_if((string) $acid !== $request->input('acid'), 404);

        if (CuratedRegisterActivity::whereRegisterId($crid)->whereReplyToId($acid)->exists()) {
            return redirect()->back()->withErrors(['code' => 'You already replied to this request.']);
        }

        $act = CuratedRegisterActivity::create([
            'register_id' => $crid,
            'reply_to_id' => $acid,
            'type' => 'user_response',
            'message' => $request->input('response'),
            'from_user' => true,
            'action_required' => true,
        ]);

        CuratedRegister::findOrFail($crid)->update(['user_has_responded' => true]);
        $request->session()->pull('cur-reg-con');
        $request->session()->pull('cur-reg-con-attempt');

        return view('auth.curated-register.user_response_sent');
    }

    public function conciergeStore(Request $request)
    {
        abort_if($request->user(), 404);
        $this->preCheck(true);
        $rules = [
            'sid' => 'required_if:action,email|integer|min:1|max:20000000',
            'id' => 'required_if:action,email|integer|min:1|max:20000000',
            'code' => 'required_if:action,email',
            'action' => 'required|string|in:email,message',
            'email' => 'required_if:action,email|email',
            'response' => 'required_if:action,message|string|min:20|max:1000',
        ];
        $messages = [];
        if (config('instance.curated_registration.captcha_enabled')) {
            $rules['h-captcha-response'] = 'required|captcha';
            $messages['h-captcha-response.required'] = 'The captcha must be filled';
        }
        $this->validate($request, $rules, $messages);

        $action = $request->input('action');
        $sid = $request->input('sid');
        $id = $request->input('id');
        $code = $request->input('code');
        $email = $request->input('email');

        $cr = CuratedRegister::whereIsClosed(false)->findOrFail($sid);
        $ac = CuratedRegisterActivity::whereRegisterId($cr->id)->whereFromAdmin(true)->findOrFail($id);

        if (! hash_equals($ac->secret_code, $code)) {
            return redirect()->back()->withErrors(['code' => 'Invalid code']);
        }

        if (! hash_equals($cr->email, $email)) {
            return redirect()->back()->withErrors(['email' => 'Invalid email']);
        }

        $request->session()->put('cur-reg-con.email-confirmed', true);
        $request->session()->put('cur-reg-con.cr-id', $cr->id);
        $request->session()->put('cur-reg-con.ac-id', $ac->id);
        $emailConfirmed = true;

        return redirect('/auth/sign_up/concierge/form');
    }

    public function confirmEmail(Request $request)
    {
        if ($request->user()) {
            return redirect(route('help.email-confirmation-issues'));
        }
        $this->preCheck(true);

        return view('auth.curated-register.confirm_email');
    }

    public function emailConfirmed(Request $request)
    {
        if ($request->user()) {
            return redirect(route('help.email-confirmation-issues'));
        }
        $this->preCheck(true);

        return view('auth.curated-register.email_confirmed');
    }

    public function resendConfirmation(Request $request)
    {
        if ($request->user()) {
            return redirect(route('help.email-confirmation-issues'));
        }
        $this->preCheck(true);

        return view('auth.curated-register.resend-confirmation');
    }

    public function resendConfirmationProcess(Request $request)
    {
        if ($request->user()) {
            return redirect(route('help.email-confirmation-issues'));
        }
        $this->preCheck(true);
        $rules = [
            'email' => [
                'required',
                'string',
                app()->environment() === 'production' ? 'email:rfc,dns,spoof' : 'email',
                'exists:curated_registers',
            ],
        ];

        $messages = [];

        if (config('instance.curated_registration.captcha_enabled')) {
            $rules['h-captcha-response'] = 'required|captcha';
            $messages['h-captcha-response.required'] = 'The captcha must be filled';
        }

        $this->validate($request, $rules, $messages);

        $cur = CuratedRegister::whereEmail($request->input('email'))->whereIsClosed(false)->first();
        if (! $cur) {
            return redirect()->back()->withErrors(['email' => 'The selected email is invalid.']);
        }

        $totalCount = CuratedRegisterActivity::whereRegisterId($cur->id)
            ->whereType('user_resend_email_confirmation')
            ->count();

        if ($totalCount && $totalCount >= config('instance.curated_registration.resend_confirmation_limit')) {
            return redirect()->back()->withErrors(['email' => 'You have re-attempted too many times. To proceed with your application, please <a href="/site/contact" class="text-white" style="text-decoration: underline;">contact the admin team</a>.']);
        }

        $count = CuratedRegisterActivity::whereRegisterId($cur->id)
            ->whereType('user_resend_email_confirmation')
            ->where('created_at', '>', now()->subHours(12))
            ->count();

        if ($count) {
            return redirect()->back()->withErrors(['email' => 'You can only re-send the confirmation email once per 12 hours. Try again later.']);
        }

        DB::transaction(function () use ($cur) {
            $cur->verify_code = Str::random(40);
            $cur->created_at = now();
            $cur->save();

            CuratedRegisterActivity::create([
                'register_id' => $cur->id,
                'type' => 'user_resend_email_confirmation',
                'admin_only_view' => true,
                'from_admin' => false,
                'from_user' => false,
                'action_required' => false,
            ]);

            Mail::to($cur->email)->send(new CuratedRegisterConfirmEmail($cur));
        });

        return view('auth.curated-register.resent-confirmation');
    }

    public function confirmEmailHandle(Request $request)
    {
        if ($request->user()) {
            return redirect(route('help.email-confirmation-issues'));
        }
        $this->preCheck(true);
        $rules = [
            'sid' => 'required',
            'code' => 'required',
        ];
        $messages = [];
        if (config('instance.curated_registration.captcha_enabled')) {
            $rules['h-captcha-response'] = 'required|captcha';
            $messages['h-captcha-response.required'] = 'The captcha must be filled';
        }
        $this->validate($request, $rules, $messages);

        $cr = CuratedRegister::whereNull('email_verified_at')
            ->where('created_at', '>', now()->subDays(7))
            ->find($request->input('sid'));
        if (! $cr) {
            return redirect(route('help.email-confirmation-issues'));
        }
        if (! hash_equals($cr->verify_code, $request->input('code'))) {
            return redirect(route('help.email-confirmation-issues'));
        }
        $cr->email_verified_at = now();
        $cr->save();

        if (config('instance.curated_registration.notify.admin.on_verify_email.enabled')) {
            CuratedOnboardingNotifyAdminNewApplicationPipeline::dispatch($cr);
        }

        return view('auth.curated-register.email_confirmed');
    }

    public function proceed(Request $request)
    {
        if ($request->user()) {
            return redirect(route('help.email-confirmation-issues'));
        }
        $this->preCheck(false);
        $this->validate($request, [
            'step' => 'required|integer|in:1,2,3,4',
        ]);
        $step = $request->input('step');

        switch ($step) {
            case 1:
                $step = 2;
                $request->session()->put('cur-step', 1);

                return view('auth.curated-register.index', compact('step'));

            case 2:
                $this->stepTwo($request);
                $step = 3;
                $request->session()->put('cur-step', 2);

                return view('auth.curated-register.index', compact('step'));

            case 3:
                $this->stepThree($request);
                $step = 3;
                $request->session()->put('cur-step', 3);
                $verifiedEmail = true;
                $request->session()->pull('cur-reg');

                return view('auth.curated-register.index', compact('step', 'verifiedEmail'));
        }
    }

    protected function stepTwo($request)
    {
        if ($request->filled('reason')) {
            $request->session()->put('cur-reg.form-reason', $request->input('reason'));
        }
        if ($request->filled('username')) {
            $request->session()->put('cur-reg.form-username', $request->input('username'));
        }
        if ($request->filled('email')) {
            $request->session()->put('cur-reg.form-email', $request->input('email'));
        }
        $this->validate($request, [
            'username' => [
                'required',
                'min:2',
                'max:30',
                'unique:curated_registers',
                'unique:users',
                function ($attribute, $value, $fail) {
                    $dash = substr_count($value, '-');
                    $underscore = substr_count($value, '_');
                    $period = substr_count($value, '.');

                    if (ends_with($value, ['.php', '.js', '.css'])) {
                        return $fail('Username is invalid.');
                    }

                    if (($dash + $underscore + $period) > 1) {
                        return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
                    }

                    if (! ctype_alnum($value[0])) {
                        return $fail('Username is invalid. Must start with a letter or number.');
                    }

                    if (! ctype_alnum($value[strlen($value) - 1])) {
                        return $fail('Username is invalid. Must end with a letter or number.');
                    }

                    $val = str_replace(['_', '.', '-'], '', $value);
                    if (! ctype_alnum($val)) {
                        return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
                    }

                    $restricted = RestrictedNames::get();
                    if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
                        return $fail('Username cannot be used.');
                    }
                },
            ],
            'email' => [
                'required',
                'string',
                app()->environment() === 'production' ? 'email:rfc,dns,spoof' : 'email',
                'max:255',
                'unique:users',
                'unique:curated_registers',
                function ($attribute, $value, $fail) {
                    $banned = EmailService::isBanned($value);
                    if ($banned) {
                        return $fail('Email is invalid.');
                    }
                },
            ],
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
            'reason' => 'required|min:20|max:1000',
            'agree' => 'required|accepted',
        ]);
        $request->session()->put('cur-reg.form-email', $request->input('email'));
        $request->session()->put('cur-reg.form-password', $request->input('password'));
    }

    protected function stepThree($request)
    {
        $this->validate($request, [
            'email' => [
                'required',
                'string',
                app()->environment() === 'production' ? 'email:rfc,dns,spoof' : 'email',
                'max:255',
                'unique:users',
                'unique:curated_registers',
                function ($attribute, $value, $fail) {
                    $banned = EmailService::isBanned($value);
                    if ($banned) {
                        return $fail('Email is invalid.');
                    }
                },
            ],
        ]);
        $cr = new CuratedRegister;
        $cr->email = $request->email;
        $cr->username = $request->session()->get('cur-reg.form-username');
        $cr->password = bcrypt($request->session()->get('cur-reg.form-password'));
        $cr->ip_address = $request->ip();
        $cr->reason_to_join = $request->session()->get('cur-reg.form-reason');
        $cr->verify_code = Str::random(40);
        $cr->save();

        Mail::to($cr->email)->send(new CuratedRegisterConfirmEmail($cr));
    }
}
