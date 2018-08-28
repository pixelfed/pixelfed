<?php

namespace App\Util\Identicon\Preprocessor;

class HashPreprocessor implements \Bitverse\Identicon\Preprocessor\PreprocessorInterface
{
    protected $algo = 'sha256';

    public function __construct($algo = 'sha256')
    {
        $this->algo = $algo;
    }

    /**
     * {@inheritdoc}
     */
    public function process($string)
    {
        return hash($this->algo, $string);
    }
}
