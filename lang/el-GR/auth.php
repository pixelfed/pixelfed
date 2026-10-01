<?php

return [

    'verifyYourEmailAddress' => ' - Επαλήθευσε Τη Διεύθυνση Email Σου',
    'loginTitle' => 'Είσοδος Χρήστη',
    'failed' => 'Αυτά τα στοιχεία δεν υπάρχουν στα κατάστιχά μας.',
    'throttle' => 'Λόγω πολλαπλών δοκιμών, παρακαλώ δοκιμάστε ξανά σε :seconds δευτερόλεπτα.',
    'password' => 'Κωδικός πρόσβασης',
    'remember' => 'Να με θυμάσαι',
    'forgot' => 'Ξέχασα Τον Κωδικό',
    'login' => 'Είσοδος',

    'register' => 'Εγγραφή',
    'reset' => 'Επαναφορά Κωδικού Πρόσβασης',

    'name' => 'Όνομα',
    'username' => 'Όνομα χρήστη',
    'confirm-password' => 'Επιβεβαίωση Κωδικού',

    'age' => ''.(int) config('pixelfed.min_registration_age', 16).'',
    'terms' => 'Με την εγγραφή, συμφωνείτε με τους <a href="'.route('site.terms').'" class="font-weight-bold text-dark">Όρους Χρήσης</a> και τη <a href="'.route('site.privacy').'" class="font-weight-bold text-dark">Πολιτική απορρήτου</a>.',

    'emailAddress' => 'Διεύθυνση Email',
    'email' => 'E-mail',
    'forgotEmail' => 'Ξέχασα το e-mail',

    'registerTitle' => 'Εγγραφή νέου λογαριασμού',

    'sendReset' => 'Αποστολή συνδέσμου επαναφοράς κωδικού',
    'backLogin' => 'Πίσω στην Σύνδεση Χρήστη',

    'signInMastodon' => 'Σύνδεση με Mastodon',

];
