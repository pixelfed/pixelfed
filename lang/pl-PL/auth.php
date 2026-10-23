<?php

return [

    'verifyYourEmailAddress' => '',
    'loginTitle' => '',
    'failed' => 'Wprowadzone dane logowania nie są zgodne z naszą bazą danych.',
    'throttle' => 'Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za :seconds sekund.',
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
