<?php

namespace Tests\Unit;

use phpseclib3\Crypt\RSA;
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
    public function library_installed()
    {
        $this->assertTrue(class_exists('\phpseclib3\Crypt\RSA'));
    }

    #[Test]
    public function rsa_signing()
    {
        $private = RSA::createKey();
        $publicKey = $private->getPublicKey();

        $plaintext = 'pixelfed rsa test';
        $signature = $private->sign($plaintext);

        $this->assertTrue($publicKey->verify($plaintext, $signature));
    }
}
