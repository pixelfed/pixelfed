<?php

namespace App\Util\HttpSignatures;

class KeyStore implements KeyStoreInterface
{
    /** @var Key[] */
    private $keys;

    /**
     * @param array $keys
     */
    public function __construct($keys)
    {
        $this->keys = [];
        foreach ($keys as $id => $key) {
            $this->keys[$id] = new Key($id, $key);
        }
    }

    /**
     * @param string $keyId
     *
     * @return Key
     *
     * @throws KeyStoreException
     */
    public function fetch($keyId)
    {
        if (isset($this->keys[$keyId])) {
            return $this->keys[$keyId];
        } else {
            throw new KeyStoreException("Key '$keyId' not found");
        }
    }
}
