<?php

namespace Tests\Unit;

use Tests\TestCase;

class CryptoTest extends TestCase
{
    /**
     * A basic test to check if PHPSecLib is installed.
     *
     * @return void
     */
    public function testLibraryInstalled()
    {
        $this->assertTrue(class_exists('\phpseclib\Crypt\RSA'));
    }

    public function testRSASigning()
    {
        $rsa = new \phpseclib\Crypt\RSA();
        extract($rsa->createKey());
        $rsa->loadKey($privatekey);
        $plaintext = 'pixelfed rsa test';
        $signature = $rsa->sign($plaintext);
        $rsa->loadKey($publickey);
        $this->assertTrue($rsa->verify($plaintext, $signature));
    }
}
