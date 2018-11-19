<?php

namespace App\Util\HttpSignatures;

class SignatureParametersParser
{
    /** @var string */
    private $input;

    /**
     * @param string $input
     */
    public function __construct($input)
    {
        $this->input = $input;
    }

    /**
     * @return array
     */
    public function parse()
    {
        $result = $this->pairsToAssociative(
            $this->arrayOfPairs()
        );
        $this->validate($result);

        return $result;
    }

    /**
     * @param array $pairs
     *
     * @return array
     */
    private function pairsToAssociative($pairs)
    {
        $result = [];
        foreach ($pairs as $pair) {
            $result[$pair[0]] = $pair[1];
        }

        return $result;
    }

    /**
     * @return array
     */
    private function arrayOfPairs()
    {
        return array_map(
            [$this, 'pair'],
            $this->segments()
        );
    }

    /**
     * @return array
     */
    private function segments()
    {
        return explode(',', $this->input);
    }

    /**
     * @param $segment
     *
     * @return array
     *
     * @throws SignatureParseException
     */
    private function pair($segment)
    {
        $segmentPattern = '/\A(keyId|algorithm|headers|signature)="(.*)"\z/';
        $matches = [];
        $result = preg_match($segmentPattern, $segment, $matches);
        if (1 !== $result) {
            throw new SignatureParseException("Signature parameters segment '$segment' invalid");
        }
        array_shift($matches);

        return $matches;
    }

    /**
     * @param $result
     *
     * @throws SignatureParseException
     */
    private function validate($result)
    {
        $this->validateAllKeysArePresent($result);
    }

    /**
     * @param $result
     *
     * @throws SignatureParseException
     */
    private function validateAllKeysArePresent($result)
    {
        // Regexp in pair() ensures no unwanted keys exist.
        // Ensure that all wanted keys exist.
        $wanted = ['keyId', 'algorithm', 'headers', 'signature'];
        $missing = array_diff($wanted, array_keys($result));
        if (!empty($missing)) {
            $csv = implode(', ', $missing);
            throw new SignatureParseException("Missing keys $csv");
        }
    }
}
