<?php

namespace Tests\Unit\HttpSignatures;

use App\Util\HttpSignatures\KeyStore;

class KeyStoreTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException App\Util\HttpSignatures\Exception
     */
    public function testFetchFail()
    {
        $ks = new KeyStore(['id' => 'secret']);
        $key = $ks->fetch('nope');
    }
}
