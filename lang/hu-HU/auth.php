<?php

return [

    'verifyYourEmailAddress' => ' - E-mail-cím megerősítése',
    'loginTitle' => 'Bejelentkezés',
    'failed' => 'Ezek a hitelesítő adatok nem egyeznek a rögzítettekkel.',
    'throttle' => 'Túl sok bejelentkezési kísérlet. Próbálja meg újra :seconds másodperc múlva.',
    'password' => 'Jelszó',
    'remember' => 'Emlékezzen rám',
    'forgot' => 'Elfelejtett jelszó',
    'login' => 'Bejelentkezés',

    'register' => 'Regisztráció',
    'reset' => 'Jelszó visszaállítása',

    'name' => 'Név',
    'username' => 'Felhasználónév',
    'confirm-password' => 'Jelszó megerősítése',

    'age' => 'Legalább'.(int) config('pixelfed.min_registration_age', 16).'éves vagyok',
    'terms' => 'A regisztrációval elfogadja a következőket: <a href="'.route('site.terms').'" class="font-weight-bold text-dark">Felhasználási feltételek</a> and <a href="'.route('site.privacy').'" class="font-weight-bold text-dark">Adatvédelmi szabályzat</a>.',

    'emailAddress' => 'E-mail-cím',
    'email' => 'E-mail',
    'forgotEmail' => 'Elfelejtett e-mail',

    'registerTitle' => 'Új fiók regisztrációja',

    'sendReset' => 'Jelszó-visszaállító hivatkozás küldése',
    'backLogin' => 'Vissza a bejelentkezéshez',

    'signInMastodon' => 'Bejelentkezés Mastodon-fiókkal',

];
