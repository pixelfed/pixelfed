<?php

return [

    'verifyYourEmailAddress' => ' - Verificatz vòstra adreça electronica',
    'loginTitle' => 'Identificant de connexion',
    'failed' => 'Aqueles identificants correspondon pas a nòstres enregistraments.',
    'throttle' => 'Tròp d’ensages de connexion. Tornatz ensajar dins :seconds segondas.',
    'password' => 'Senhal',
    'remember' => 'Se remembrar de ieu',
    'forgot' => 'Senhal oblidat',
    'login' => 'Se connectar',

    'register' => 'Se marcar',
    'reset' => 'Reinicializacion del senhal',

    'name' => 'Nom',
    'username' => 'Nom d’utilizaire',
    'confirm-password' => '',

    'age' => ''.(int) config('pixelfed.min_registration_age', 16).'',
    'terms' => ''.route('site.terms').''.route('site.privacy').'',

    'emailAddress' => 'Adreça electronica',
    'email' => 'E-mail',
    'forgotEmail' => 'E-mail oblidat',

    'registerTitle' => 'Se marcar amb un compte nòu',

    'sendReset' => '',
    'backLogin' => '',

    'signInMastodon' => '',

];
