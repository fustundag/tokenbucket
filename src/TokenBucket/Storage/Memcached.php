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
     * @return array
     */
    public function getResultCode()
    {
        return $this->memcachedObj->getResultCode();
    }

    public function get($key)
    {
        $data       = $this->memcachedObj->get($key, null, $cas);
        $resultCode = $this->memcachedObj->getResultCode();
        if ($resultCode != \Memcached::RES_SUCCESS && $resultCode != \Memcached::RES_NOTFOUND) {
            throw new StorageException(
                '[STORAGE] "'. $this->getStorageName().'::get" failed for key "'. $key .'"!'
                .' StorageRespCode: ' . $resultCode
            );
        }
        $this->casArray[ $key ] = $cas;
        return $data;
    }

    public function set($key, $value, $ttl = 0)
    {
        if (isset($this->casArray[ $key ])===false) {
            $this->get($key);
        }
        if (!$value) {
            throw new StorageException(
                '[STORAGE] Given value for "'. $this->getStorageName() .'::set" not valid! Key: ' . $key
            );
        }
        if ($this->casArray[ $key ]==0) {
            $this->memcachedObj->add($key, $value, $ttl);
            unset($this->casArray[ $key ]);
        } else {
            $this->memcachedObj->cas($this->casArray[ $key ], $key, $value, $ttl);
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
}
