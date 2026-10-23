<?php

return [

    'verifyYourEmailAddress' => '',
    'loginTitle' => '',
    'failed' => 'اطلاعات ورود صحیح نیست.',
    'throttle' => 'شما درخواست تکراری زیادی فرستادید. لطفا مجددا در  :seconds ثانیه دیگر تلاش کنید.',
    'password' => '',
    'remember' => '',
    'forgot' => '',
    'login' => '',

    'register' => '',
    'reset' => '',

    'name' => '',
    'username' => '',
    'confirm-password' => '',

    'age' => ''.(int) config('pixelfed.min_registration_age', 16).'',
    'terms' => ''.route('site.terms').''.route('site.privacy').'',

    'emailAddress' => '',
    'email' => '',
    'forgotEmail' => '',

    'registerTitle' => '',

    'sendReset' => '',
    'backLogin' => '',

    'signInMastodon' => '',

];
