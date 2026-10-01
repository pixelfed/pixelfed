<?php

return [

    'verifyYourEmailAddress' => '',
    'loginTitle' => 'Paskyros prisijungimas',
    'failed' => '',
    'throttle' => '',
    'password' => 'Slaptažodis',
    'remember' => 'Prisiminti mane',
    'forgot' => 'Pamiršau slaptažodį',
    'login' => 'Prisijungti',

    'register' => 'Registruotis',
    'reset' => '',

    'name' => '',
    'username' => '',
    'confirm-password' => '',

    'age' => ''.(int) config('pixelfed.min_registration_age', 16).'',
    'terms' => ''.route('site.terms').''.route('site.privacy').'',

    'emailAddress' => 'El. pašto adresas',
    'email' => 'El. paštas',
    'forgotEmail' => 'Pamiršau el. paštą',

    'registerTitle' => '',

    'sendReset' => '',
    'backLogin' => 'Grįžti į prisijungimą',

    'signInMastodon' => 'Prisijungti su „Mastodon“',

];
