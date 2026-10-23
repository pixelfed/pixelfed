<?php

return [

    'verifyYourEmailAddress' => '',
    'loginTitle' => '',
    'failed' => 'As credenciais non constan nos nosos rexistros.',
    'throttle' => 'Demasiados intentos de conexión. Por favor, inténtao de novo en :seconds segundos.',
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
