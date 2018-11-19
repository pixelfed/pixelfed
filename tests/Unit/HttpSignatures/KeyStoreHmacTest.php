<?php

namespace Tests\Unit\HttpSignatures;

use App\Util\HttpSignatures\KeyStore;

class KeyStoreHmacTest extends \PHPUnit\Framework\TestCase
{
    public function testFetchHmacSuccess()
    {
        $ks = new KeyStore(['hmacsecret' => 'ThisIsASecretKey']);
        $key = $ks->fetch('hmacsecret');
        $this->assertEquals(['hmacsecret', 'ThisIsASecretKey', 'ThisIsASecretKey', 'secret'], [
          $key->getId(), $key->getVerifyingKey(), $key->getSigningKey(), $key->getType(), ]);
    }
}
