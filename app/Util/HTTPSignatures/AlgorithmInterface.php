<?php

namespace App\Util\HttpSignatures;

interface AlgorithmInterface
{
    /**
     * @return string
     */
    public function name();

    /**
     * @param string $key
     * @param string $data
     *
     * @return string
     */
    public function sign($key, $data);
}
