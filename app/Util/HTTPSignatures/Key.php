<?php

namespace App\Util\HttpSignatures;

class Key
{
    /** @var string */
    private $id;

    /** @var string */
    private $secret;

    /** @var resource */
    private $certificate;

    /** @var resource */
    private $publicKey;

    /** @var resource */
    private $privateKey;

    /** @var string */
    private $type;

    /**
     * @param string       $id
     * @param string|array $secret
     */
    public function __construct($id, $item)
    {
        $this->id = $id;
        if (Key::hasX509Certificate($item) || Key::hasPublicKey($item)) {
            $publicKey = Key::getPublicKey($item);
        } else {
            $publicKey = null;
        }
        if (Key::hasPrivateKey($item)) {
            $privateKey = Key::getPrivateKey($item);
        } else {
            $privateKey = null;
        }
        if (($publicKey || $privateKey)) {
            $this->type = 'asymmetric';
            if ($publicKey && $privateKey) {
                $publicKeyPEM = openssl_pkey_get_details($publicKey)['key'];
                $privateKeyPublicPEM = openssl_pkey_get_details($privateKey)['key'];
                if ($privateKeyPublicPEM != $publicKeyPEM) {
                    throw new KeyException('Supplied Certificate and Key are not related');
                }
            }
            $this->privateKey = $privateKey;
            $this->publicKey = $publicKey;
            $this->secret = null;
        } else {
            $this->type = 'secret';
            $this->secret = $item;
            $this->publicKey = null;
            $this->privateKey = null;
        }
    }

    /**
     * Retrieves private key resource from a input string or
     * array of strings.
     *
     * @param string|array $object PEM-format Private Key or file path to same
     *
     * @return resource|false
     */
    public static function getPrivateKey($object)
    {
        if (is_array($object)) {
            foreach ($object as $candidateKey) {
                $privateKey = Key::getPrivateKey($candidateKey);
                if ($privateKey) {
                    return $privateKey;
                }
            }
        } else {
            // OpenSSL libraries don't have detection methods, so try..catch
            try {
                $privateKey = openssl_get_privatekey($object);

                return $privateKey;
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    /**
     * Retrieves public key resource from a input string or
     * array of strings.
     *
     * @param string|array $object PEM-format Public Key or file path to same
     *
     * @return resource|false
     */
    public static function getPublicKey($object)
    {
        if (is_array($object)) {
            // If we implement key rotation in future, this should add to a collection
            foreach ($object as $candidateKey) {
                $publicKey = Key::getPublicKey($candidateKey);
                if ($publicKey) {
                    return $publicKey;
                }
            }
        } else {
            // OpenSSL libraries don't have detection methods, so try..catch
            try {
                $publicKey = openssl_get_publickey($object);

                return $publicKey;
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    /**
     * Signing HTTP Messages 'keyId' field.
     *
     * @return string
     *
     * @throws KeyException
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retrieve Verifying Key - Public Key for Asymmetric/PKI, or shared secret for HMAC.
     *
     * @return string Shared Secret or PEM-format Public Key
     *
     * @throws KeyException
     */
    public function getVerifyingKey()
    {
        switch ($this->type) {
        case 'asymmetric':
            if ($this->publicKey) {
                return openssl_pkey_get_details($this->publicKey)['key'];
            } else {
                return null;
            }
            break;
        case 'secret':
            return $this->secret;
        default:
            throw new KeyException("Unknown key type $this->type");
        }
    }

    /**
     * Retrieve Signing Key - Private Key for Asymmetric/PKI, or shared secret for HMAC.
     *
     * @return string Shared Secret or PEM-format Private Key
     *
     * @throws KeyException
     */
    public function getSigningKey()
    {
        switch ($this->type) {
        case 'asymmetric':
            if ($this->privateKey) {
                openssl_pkey_export($this->privateKey, $pem);

                return $pem;
            } else {
                return null;
            }
            break;
        case 'secret':
            return $this->secret;
        default:
            throw new KeyException("Unknown key type $this->type");
        }
    }

    /**
     * @return string 'secret' for HMAC or 'asymmetric'
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Test if $object is, points to or contains, X.509 PEM-format certificate.
     *
     * @param string|array $object PEM Format X.509 Certificate or file path to one
     *
     * @return bool
     */
    public static function hasX509Certificate($object)
    {
        if (is_array($object)) {
            foreach ($object as $candidateCertificate) {
                $result = Key::hasX509Certificate($candidateCertificate);
                if ($result) {
                    return $result;
                }
            }
        } else {
            // OpenSSL libraries don't have detection methods, so try..catch
            try {
                openssl_x509_export($object, $null);

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * Test if $object is, points to or contains, PEM-format Public Key.
     *
     * @param string|array $object PEM-format Public Key or file path to one
     *
     * @return bool
     */
    public static function hasPublicKey($object)
    {
        if (is_array($object)) {
            foreach ($object as $candidatePublicKey) {
                $result = Key::hasPublicKey($candidatePublicKey);
                if ($result) {
                    return $result;
                }
            }
        } else {
            return false == !openssl_pkey_get_public($object);
        }
    }

    /**
     * Test if $object is, points to or contains, PEM-format Private Key.
     *
     * @param string|array $object PEM-format Private Key or file path to one
     *
     * @return bool
     */
    public static function hasPrivateKey($object)
    {
        if (is_array($object)) {
            foreach ($object as $candidatePrivateKey) {
                $result = Key::hasPrivateKey($candidatePrivateKey);
                if ($result) {
                    return $result;
                }
            }
        } else {
            return  false != openssl_pkey_get_private($object);
        }
    }
}
