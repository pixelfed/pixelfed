<?php

return [

    'verifyYourEmailAddress' => ' - Dearbh an seòladh puist-d agad',
    'loginTitle' => 'Clàradh a-steach dhan chunntas',
    'failed' => 'Chan eil an teisteas seo a-rèir nan clàran againn.',
    'throttle' => 'Cus oidhirpean clàraidh a-steach. Feuch ris a-rithist an ceann :seconds diog(an).',
    'password' => 'Facal-faire',
    'remember' => 'Cùm an cuimhne mi',
    'forgot' => 'Dhìochuimhnich mi am facal-faire',
    'login' => 'Clàraich a-steach',

    'register' => 'Clàraich leinn',
    'reset' => 'Ath-shuidheachadh an fhacail-fhaire',

    'name' => 'Ainm',
    'username' => 'Ainm-cleachdaiche',
    'confirm-password' => 'Dearbh am facal-faire',

    'age' => 'Tha mi co-dhiù '.(int) config('pixelfed.min_registration_age', 16).' bliadhna a dh’aois',
    'terms' => 'Nuair a chlàraicheas tu leinn, aontaichidh tu ris na <a href="'.route('site.terms').'" class="font-weight-bold text-dark">Teirmichean cleachdaidh</a> agus am <a href="'.route('site.privacy').'" class="font-weight-bold text-dark">Poileasaidh prìobhaideachd</a> againn.',

    'emailAddress' => 'Seòladh puist-d',
    'email' => 'Post-d',
    'forgotEmail' => 'Dhìochuimhnich mi am post-d',

    'registerTitle' => 'Clàraich cunntas ùr',

    'sendReset' => 'Cuir ceangal ath-shuidheachadh an fhachail-fhaire',
    'backLogin' => 'Air ais dhan chlàradh a-steach',

    'signInMastodon' => 'Clàraich a-steach le Mastodon',

];
