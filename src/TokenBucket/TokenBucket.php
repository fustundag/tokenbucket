<?php
/**
 * Copyright (c) Fatih Ustundag <fatih.ustundag@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TokenBucket;

use TokenBucket\Storage\StorageInterface;

class TokenBucket
{
    private $bucketKeyPrefix = 'TokenBucket.';

    /**
     * Max capacity for bucket
     * @var int
     */
    private $capacity = 20;

    /**
     * Fill rate per second
     * @var int
     */
    private $fillRate = 5;

    /**
     * @var float
     */
    private $ttl = 6.0;

    /**
     * @var array
     */
    private $bucket = array();

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var string
     */
    private $bucketKey = '';

    public function __construct($key, StorageInterface $storage, $options = array())
    {
        $this->storage   = $storage;
        $this->bucketKey = $this->bucketKeyPrefix . $key;
        $this->setOptions($options);
    }

    /**
     * @return array
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return string
     */
    public function getBucketKey()
    {
        return $this->bucketKey;
    }

    /**
     * @return int
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @return int
     */
    public function getFillRate()
    {
        return $this->fillRate;
    }

    /**
     * @return float
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    public function setOptions($options)
    {
        if (is_array($options) && count($options)>0) {
            $this->capacity = (isset($options['capacity']) && intval($options['capacity'])>0)
                ?intval($options['capacity'])
                :$this->capacity;

            $this->fillRate = (isset($options['fillRate']) && floatval($options['fillRate'])>0)
                ?$options['fillRate']
                :$this->fillRate;
            $this->ttl = ($this->capacity/$this->fillRate)*1.5;
        }
    }

    public function save()
    {
        $this->storage->set(
            $this->bucketKey,
            $this->bucket,
            $this->ttl
        );
    }

    public function fill()
    {
        $this->bucket = $this->storage->get($this->bucketKey);
        if (is_array($this->bucket)===false) {
            $this->bucket = array(
                'count' => $this->capacity,
                'time'  => time()
            );
        } else {
            $now = time();
            if ($this->bucket['count'] < $this->capacity) {
                $delta = $this->fillRate * ($now - $this->bucket['time']);
                $this->bucket['count'] = min($this->capacity, ($this->bucket['count'] + $delta));
            }
            $this->bucket['time'] = $now;
        }

        $this->save();
        return $this->bucket['count'];
    }

    public function consume($amount = 1)
    {
        $this->fill();
        if ($amount<=$this->bucket['count']) {
            $this->bucket['count'] -= $amount;
            $this->save();
            return true;
        } else {
            return false;
        }
    }
}
