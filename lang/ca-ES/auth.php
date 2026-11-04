<?php

return [

    'verifyYourEmailAddress' => ' -Verifica la teva adreça d\'Email',
    'loginTitle' => 'Inicia sessió al compte',
    'failed' => 'Aquestes dades d\'accés no coincideixen amb els nostres registres.',
    'throttle' => 'Massa intents d\'iniciar sessió. Si us plau, torneu-ho a provar d\'aquí a :seconds segons.',
    'password' => 'Contrasenya',
    'remember' => 'Recorda\'m',
    'forgot' => 'Contrasenya oblidada',
    'login' => 'Iniciar Sessió',

    'register' => 'Registrar-se',
    'reset' => 'Reiniciar contrasenya',

    'name' => 'Nom',
    'username' => 'Nom d’usuari',
    'confirm-password' => 'Confirma la contrasenya',

    'age' => 'Tinc almenys '.(int) config('pixelfed.min_registration_age', 16).' anys',
    'terms' => 'Quan et registres, acceptes als nostres <a href="'.route('site.terms').''.route('site.privacy').'',

    'emailAddress' => 'Adreça d\'E-Mail',
    'email' => 'E-Mail',
    'forgotEmail' => 'He oblidat l\'E-Mail',

    'registerTitle' => 'Registrar un nou compte',

    'sendReset' => 'Envia\'m enllaç per restablir la contrasenya',
    'backLogin' => 'Tornar a l\'inici de sessió',

    'signInMastodon' => 'Inicia sessió amb Mastodon',

];
