<?php

namespace Tests\Unit\HttpSignatures;

use App\Util\HttpSignatures\KeyStore;
use App\Util\HttpSignatures\Key;
use Tests\Unit\HttpSignatures\TestKeys;

class KeyStoreRsaTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        openssl_pkey_export(
            openssl_pkey_get_private(TestKeys::rsaPrivateKey),
            $this->testRsaPrivateKeyPEM
        );
        $this->testRsaPublicKeyPEM = openssl_pkey_get_details(
            openssl_get_publickey(TestKeys::rsaPublicKey)
        )['key'];
        $this->testRsaCert = TestKeys::rsaCert;
    }

    public function testParseX509inObject()
    {
        $keySpec = ['rsaCert' => [TestKeys::rsaCert]];
        $this->assertTrue(Key::hasX509Certificate($keySpec));

        $ks = new KeyStore($keySpec);
        $publicKey = $ks->fetch('rsaCert')->getVerifyingKey();
        $this->assertEquals('asymmetric', $ks->fetch('rsaCert')->getType());
        $this->assertEquals(TestKeys::rsaPublicKey, $publicKey);
    }

    public function testParseRsaPublicKeyinObject()
    {
        $keySpec = ['rsaPubKey' => [TestKeys::rsaPublicKey]];
        $this->assertTrue(Key::hasPublicKey($keySpec));

        $ks = new KeyStore($keySpec);
        $publicKey = $ks->fetch('rsaPubKey')->getVerifyingKey();
        $this->assertEquals('asymmetric', $ks->fetch('rsaPubKey')->getType());
        $this->assertEquals(TestKeys::rsaPublicKey, $publicKey);
    }

    public function testParsePrivateKeyinObject()
    {
        $keySpec = ['rsaPrivKey' => [TestKeys::rsaPrivateKey]];
        $this->assertTrue(Key::hasPrivateKey($keySpec));

        $ks = new KeyStore($keySpec);
        $publicKey = $ks->fetch('rsaPrivKey')->getSigningKey();
        $this->assertEquals('asymmetric', $ks->fetch('rsaPrivKey')->getType());
        $this->assertEquals($this->testRsaPrivateKeyPEM, $publicKey);
    }

    public function testFetchRsaSigningKeySuccess()
    {
        $ks = new KeyStore(['rsakey' => TestKeys::rsaPrivateKey]);
        $key = $ks->fetch('rsakey');
        openssl_pkey_export($key->getSigningKey(), $keyStoreSigningKey);
        $this->assertEquals(['rsakey', $this->testRsaPrivateKeyPEM, null, 'asymmetric'], [
          $key->getId(), $keyStoreSigningKey, $key->getVerifyingKey(), $key->getType(), ]);
    }

    public function testFetchRsaVerifyingKeyFromCertificateSuccess()
    {
        $ks = new KeyStore(['rsacert' => TestKeys::rsaCert]);
        $key = $ks->fetch('rsacert');
        $keyStoreVerifyingKey = $key->getVerifyingKey();
        $this->assertEquals(['rsacert', null, $this->testRsaPublicKeyPEM, 'asymmetric'], [
          $key->getId(), $key->getSigningKey(), $keyStoreVerifyingKey, $key->getType(), ]);
    }

    public function testFetchRsaVerifyingKeyFromPublicKeySuccess()
    {
        $ks = new KeyStore(['rsapubkey' => TestKeys::rsaPublicKey]);
        $key = $ks->fetch('rsapubkey');
        $keyStoreVerifyingKey = $key->getVerifyingKey();
        $this->assertEquals(['rsapubkey', null, $this->testRsaPublicKeyPEM, 'asymmetric'], [
          $key->getId(), $key->getSigningKey(), $keyStoreVerifyingKey, $key->getType(), ]);
    }

    public function testFetchRsaBothSuccess()
    {
        $ks = new KeyStore(['rsaboth' => [TestKeys::rsaCert, TestKeys::rsaPrivateKey]]);
        $key = $ks->fetch('rsaboth');
        $keyStoreVerifyingKey = $key->getVerifyingKey();
        $keyStoreSigningKey = $key->getSigningKey();
        $this->assertEquals(['rsaboth', $this->testRsaPrivateKeyPEM, $this->testRsaPublicKeyPEM, 'asymmetric'], [
          $key->getId(), $keyStoreSigningKey, $keyStoreVerifyingKey, $key->getType(), ]);
    }

    public function testFetchRsaBothSuccessSwitched()
    {
        $ks = new KeyStore(['rsabothswitch' => [TestKeys::rsaPrivateKey, TestKeys::rsaCert]]);
        $key = $ks->fetch('rsabothswitch');
        $keyStoreVerifyingKey = $key->getVerifyingKey();
        $keyStoreSigningKey = $key->getSigningKey();
        $this->assertEquals(['rsabothswitch', $this->testRsaPrivateKeyPEM, $this->testRsaPublicKeyPEM, 'asymmetric'], [
          $key->getId(), $keyStoreSigningKey, $keyStoreVerifyingKey, $key->getType(), ]);
    }

    /**
     * @expectedException \App\Util\HttpSignatures\KeyException
     */
    public function testRsaMismatch()
    {
        $privateKey = openssl_pkey_new([
          'private_key_type' => 'OPENSSL_KEYTYPE_RSA',
          'private_key_bits' => 1024, ]
        );
        $ks = new Key('badpki', [TestKeys::rsaCert, $privateKey]);
    }
}