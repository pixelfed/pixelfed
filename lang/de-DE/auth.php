<?php

return [

    'verifyYourEmailAddress' => ' - Verifiziere deine E-Mail-Adresse',
    'loginTitle' => 'Kontoanmeldung',
    'failed' => 'Diese Anmeldeinformationen stimmen nicht mit unseren Daten überein.',
    'throttle' => 'Zu viele Anmeldeversuche. Versuche es in :seconds Sekunden erneut.',
    'password' => 'Passwort',
    'remember' => 'Erinnere mich',
    'forgot' => 'Passwort vergessen',
    'login' => 'Login',

    'register' => 'Konto erstellen',
    'reset' => 'Passwort zurücksetzen',

    'name' => 'Name',
    'username' => 'Nickname',
    'confirm-password' => 'Passwort bestätigen',

    'age' => 'Ich bin mindestens'.(int) config('pixelfed.min_registration_age', 16).' Jahre alt',
    'terms' => 'Mit der Anmeldung akzeptierst du unsere <a href="'.route('site.terms').'" class="font-weight-fold text-dark">Nutzungsbedingungen</a> und <a href="'.route('site.privacy').'" class="font-weight-fold text-dark">Datenschutzerklärung</a>.',

    'emailAddress' => 'E-Mail-Adresse',
    'email' => 'E-Mail',
    'forgotEmail' => 'E-Mail-Adresse vergessen',

    'registerTitle' => 'Erstelle ein neues Konto',

    'sendReset' => 'Zurücksetzungslink für das Passwort senden',
    'backLogin' => 'Zurück zur Anmeldung',

    'signInMastodon' => 'Sign-in mit Mastodon',

];
