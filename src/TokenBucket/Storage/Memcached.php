<?php

/**
 * Copyright (c) Fatih Ustundag <fatih.ustundag@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TokenBucket\Storage;

use TokenBucket\Exception\StorageException;

class Memcached implements StorageInterface
{
    /**
     * @var \Memcached|null
     */
    private $memcachedObj = null;
    private $casArray     = array();

    public function __construct(\Memcached $memcachedObj)
    {
        $this->memcachedObj = $memcachedObj;
    }

    public function getStorageName()
    {
        return 'Memcached';
    }

    /**
     * @return array
     */
    public function getCasArray()
    {
        return $this->casArray;
    }

    /**
     * @return int
     */
    public function getResultCode()
    {
        return $this->memcachedObj->getResultCode();
    }

    public function get($key)
    {
        if (self::doesGetReturnCasByReference()) {
            $casToken = null;
            $data     = $this->memcachedObj->get($key, null, $casToken);
        } else {
            $data = $this->memcachedObj->get($key, null, \Memcached::GET_EXTENDED);
            if (is_array($data)) {
                $casToken = isset($data['cas'])?$data['cas']:null;
                $data     = isset($data['value'])?$data['value']:false;
            } else {
                $casToken = null;
                $data     = false;
            }
        }

        $resultCode = $this->memcachedObj->getResultCode();
        if ($resultCode != \Memcached::RES_SUCCESS && $resultCode != \Memcached::RES_NOTFOUND) {
            throw new StorageException(
                '[STORAGE] "'. $this->getStorageName().'::get" failed for key "'. $key .'"!'
                .' StorageRespCode: ' . $resultCode
            );
        }
        $this->casArray[ $key ] = $casToken;
        return $data;
    }

    public function set($key, $value)
    {
        $this->get($key);
        if (!$value) {
            throw new StorageException(
                '[STORAGE] Given value for "'. $this->getStorageName() .'::set" not valid! Key: ' . $key
            );
        }
        if ($this->memcachedObj->getResultCode()==\Memcached::RES_NOTFOUND) {
            $this->memcachedObj->add($key, $value);
            unset($this->casArray[ $key ]);
        } else {
            $this->memcachedObj->cas($this->casArray[ $key ], $key, $value);
        }
        $resultCode = $this->memcachedObj->getResultCode();
        if ($resultCode != \Memcached::RES_SUCCESS) {
            throw new StorageException(
                '[STORAGE] "'. $this->getStorageName() .'::set" failed!'
                . ' StorageRespCode: ' . $resultCode
                . ', Key: '. $key
                . ', Cas: ' . (isset($this->casArray[ $key ])?$this->casArray[ $key ]:'null')
            );
        }
        return $resultCode;
    }

    public function delete($key)
    {
        $this->memcachedObj->delete($key);
        $resultCode = $this->memcachedObj->getResultCode();
        if ($resultCode != \Memcached::RES_SUCCESS && $resultCode != \Memcached::RES_NOTFOUND) {
            throw new StorageException(
                '[STORAGE] "'. $this->getStorageName().'::delete" failed for key "'. $key .'"!'
                .' StorageRespCode: ' . $resultCode
            );
        }
        unset($this->casArray[ $key ]);
        return $resultCode;
    }

    /**
     * @return boolean Do get() and getMulti() return $token by reference,
     * or do you have to pass Memcached::GET_EXTENDED as a bit flag in that position instead.
     */
    public static function doesGetReturnCasByReference()
    {
        static $returnsReference;
        if ($returnsReference === null) {
            // memcached < 3.0.0-dev (approx) returns the CAS token by reference.
            $returnsReference = version_compare(phpversion("memcached"), '3.0.0-dev', '<');
        }
        return $returnsReference;
    }
}
