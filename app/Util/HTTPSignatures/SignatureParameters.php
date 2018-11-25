<?php

namespace App\Util\HttpSignatures;

class SignatureParameters
{
    /**
     * @param Key                $key
     * @param AlgorithmInterface $algorithm
     * @param HeaderList         $headerList
     * @param Signature          $signature
     */
    public function __construct($key, $algorithm, $headerList, $signature)
    {
        $this->key = $key;
        $this->algorithm = $algorithm;
        $this->headerList = $headerList;
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function string()
    {
        return implode(',', $this->parameterComponents());
    }

    /**
     * @return array
     */
    private function parameterComponents()
    {
        return [
            sprintf('keyId="%s"', $this->key->getId()),
            sprintf('algorithm="%s"', $this->algorithm->name()),
            sprintf('headers="%s"', $this->headerList->string()),
            sprintf('signature="%s"', $this->signatureBase64()),
        ];
    }

    /**
     * @return string
     */
    private function signatureBase64()
    {
        return base64_encode($this->signature->string());
    }
}
