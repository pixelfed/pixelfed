<?php

namespace App\Util\HttpSignatures;

use Psr\Http\Message\RequestInterface;

class Signer
{
    /** @var Key */
    private $key;

    /** @var HmacAlgorithm */
    private $algorithm;

    /** @var HeaderList */
    private $headerList;

    /**
     * @param Key           $key
     * @param HmacAlgorithm $algorithm
     * @param HeaderList    $headerList
     */
    public function __construct($key, $algorithm, $headerList)
    {
        $this->key = $key;
        $this->algorithm = $algorithm;
        $this->headerList = $headerList;
    }

    /**
     * @param RequestInterface $message
     *
     * @return RequestInterface
     */
    public function sign($message)
    {
        $signatureParameters = $this->signatureParameters($message);
        $message = $message->withAddedHeader('Signature', $signatureParameters->string());
        $message = $message->withAddedHeader('Authorization', 'Signature '.$signatureParameters->string());

        return $message;
    }

    /**
     * @param RequestInterface $message
     *
     * @return RequestInterface
     */
    public function signWithDigest($message)
    {
        $message = $this->addDigest($message);

        return $this->sign($message);
    }

    /**
     * @param RequestInterface $message
     *
     * @return RequestInterface
     */
    private function addDigest($message)
    {
        if (!array_search('digest', $this->headerList->names)) {
            $this->headerList->names[] = 'digest';
        }
        $message = $message->withoutHeader('Digest')
            ->withHeader(
                'Digest',
                'SHA-256='.base64_encode(hash('sha256', $message->getBody(), true))
            );

        return $message;
    }

    /**
     * @param RequestInterface $message
     *
     * @return SignatureParameters
     */
    private function signatureParameters($message)
    {
        return new SignatureParameters(
            $this->key,
            $this->algorithm,
            $this->headerList,
            $this->signature($message)
        );
    }

    /**
     * @param RequestInterface $message
     *
     * @return Signature
     */
    private function signature($message)
    {
        return new Signature(
            $message,
            $this->key,
            $this->algorithm,
            $this->headerList
        );
    }
}
