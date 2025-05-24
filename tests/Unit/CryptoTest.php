<?php

namespace Tests\Unit;

use phpseclib\Crypt\RSA;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CryptoTest extends TestCase
{
    /**
     * A basic test to check if PHPSecLib is installed.
     *
     * @return void
     */
    #[Test]
    public function libraryInstalled()
    {
        $this->assertTrue(class_exists('\phpseclib\Crypt\RSA'));
    }

    #[Test]
    public function RSASigning()
    {
        $rsa = new RSA();
        extract($rsa->createKey());
        $rsa->loadKey($privatekey);
        $plaintext = 'pixelfed rsa test';
        $signature = $rsa->sign($plaintext);
        $rsa->loadKey($publickey);
        $this->assertTrue($rsa->verify($plaintext, $signature));
    }
}
