<?php

namespace App\Util\HttpSignatures;

class RsaAlgorithm implements AlgorithmInterface
{
    /** @var string */
    private $digestName;

    /**
     * @param string $digestName
     */
    public function __construct($digestName)
    {
        $this->digestName = $digestName;
    }

    /**
     * @return string
     */
    public function name()
    {
        return sprintf('rsa-%s', $this->digestName);
    }

    /**
     * @param string $key
     * @param string $data
     *
     * @return string
     *
     * @throws \HttpSignatures\AlgorithmException
     */
    public function sign($signingKey, $data)
    {
        $algo = $this->getRsaHashAlgo($this->digestName);
        if (!openssl_get_privatekey($signingKey)) {
            throw new AlgorithmException("OpenSSL doesn't understand the supplied key (not valid or not found)");
        }
        $signature = '';
        openssl_sign($data, $signature, $signingKey, $algo);

        return $signature;
    }

    public function verify($message, $signature, $verifyingKey)
    {
        $algo = $this->getRsaHashAlgo($this->digestName);

        return openssl_verify($message, base64_decode($signature), $verifyingKey, $algo);
    }

    private function getRsaHashAlgo($digestName)
    {
        switch ($digestName) {
        case 'sha256':
            return OPENSSL_ALGO_SHA256;
        case 'sha1':
            return OPENSSL_ALGO_SHA1;
        default:
            throw new HttpSignatures\AlgorithmException($digestName.' is not a supported hash format');
      }
    }
}
