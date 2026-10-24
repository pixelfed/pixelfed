<?php

return [

    'verifyYourEmailAddress' => '',
    'loginTitle' => '',
    'failed' => '',
    'throttle' => '',
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
