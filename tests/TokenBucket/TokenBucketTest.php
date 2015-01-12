<?php
/**
 * Copyright (c) Fatih Ustundag <fatih.ustundag@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TokenBucket;

use TokenBucket\Storage\Memcached as MemcachedStorage;
use TokenBucket\Storage\StorageInterface;

class TokenBucketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var TokenBucket
     */
    private $tokenBucket;

    /**
     * @var \Memcached
     */
    protected static $memcached;

    public static function setUpBeforeClass()
    {
        self::$memcached = new \Memcached();
        self::$memcached->addServer('127.0.0.1', 11211);
        self::$memcached->setOptions(array(
            \Memcached::OPT_TCP_NODELAY => true,
            \Memcached::OPT_NO_BLOCK => true,
            \Memcached::OPT_CONNECT_TIMEOUT => 100
        ));
    }

    public static function tearDownAfterClass()
    {
        self::$memcached = null;
    }

    public function setUp()
    {
        self::$memcached->flush();
        $this->storage     = new MemcachedStorage(self::$memcached);
        $this->tokenBucket = new TokenBucket('test', $this->storage);
    }

    public function tearDown()
    {
        $this->storage     = null;
        $this->tokenBucket = null;
    }

    public function testGetTtl()
    {
        $this->assertEquals(6.0, $this->tokenBucket->getTtl(), 'GetTtl failed');
    }

    public function testGetTokenCount()
    {
        $this->tokenBucket->fill();
        $this->assertEquals(20, $this->tokenBucket->getTokenCount(), 'GetTokenCount failed');
    }

    public function testGetResetTime()
    {
        $now = time();
        $this->tokenBucket->fill();
        sleep(1);
        $this->assertEquals(
            $now+$this->tokenBucket->getTtl(),
            $this->tokenBucket->getResetTime(),
            'GetTokenCount failed'
        );
    }

    public function testSetOptions()
    {
        $this->tokenBucket->setOptions(array('capacity' => 30, 'fillRate' => 10));
        $this->assertEquals(30, $this->tokenBucket->getCapacity(), 'New capacity option set failed');
        $this->assertEquals(10, $this->tokenBucket->getFillRate(), 'New fillRate option set failed');
    }

    public function testSetOptionsDefault()
    {
        $this->tokenBucket->setOptions(array('capacity' => -12, 'fillRate' => 'abc'));
        $this->assertEquals(20, $this->tokenBucket->getCapacity(), 'Default capacity option value failed');
        $this->assertEquals(5, $this->tokenBucket->getFillRate(), 'Default fillRate option value failed');
    }

    public function testFillDefault()
    {
        $this->tokenBucket->fill();
        $bucketArray = $this->tokenBucket->getBucket();
        $this->assertEquals(20, $bucketArray['count'], 'Fill does not work');
    }

    public function testFillWithFillRate()
    {
        $this->tokenBucket->setOptions(array('capacity' => 100, 'fillRate' => 10));
        $this->storage->set(
            $this->tokenBucket->getBucketKey(),
            array('count' => 50, 'time' => time(), 'reset' => time()+15),
            $this->tokenBucket->getTtl()
        );
        sleep(1);
        $this->tokenBucket->fill();
        $bucketArray = $this->tokenBucket->getBucket();
        $this->assertEquals(60, $bucketArray['count'], 'Fill does not work');

    }

    public function testFillWithoutFillRate()
    {
        $this->tokenBucket->setOptions(array('capacity' => 100, 'fillRate' => 0, 'ttl' => 5));

        $this->tokenBucket->fill();
        $bucketArray = $this->tokenBucket->getBucket();
        $this->assertEquals(100, $bucketArray['count'], 'Fill without fillrate does not work');

        $this->tokenBucket->consume(20);

        sleep(2);
        $this->tokenBucket->fill();
        $bucketArray = $this->tokenBucket->getBucket();
        $this->assertEquals(80, $bucketArray['count'], 'Fill without fillrate does not work');

        sleep(4);
        $this->tokenBucket->fill();
        $bucketArray = $this->tokenBucket->getBucket();
        $this->assertEquals(100, $bucketArray['count'], 'Fill without fillrate does not work');
    }

    public function testConsume()
    {
        $this->assertTrue($this->tokenBucket->consume(), 'Consume failed');
        $bucketArray = $this->tokenBucket->getBucket();
        $this->assertEquals(19, $bucketArray['count'], 'Token Count after consume failed');
        $this->assertNotEmpty($this->storage->get($this->tokenBucket->getBucketKey()), 'Key not found at storage');
        $this->assertArrayHasKey(
            'count',
            $this->storage->get($this->tokenBucket->getBucketKey()),
            '"count" index not found at storage key'
        );
        $storageVal = $this->storage->get($this->tokenBucket->getBucketKey());
        $this->assertEquals(
            19,
            $storageVal['count'],
            'Token Count after consume failed'
        );

        sleep(1);
        $this->assertFalse($this->tokenBucket->consume(22), 'Not Consume failed');
        $bucketArray = $this->tokenBucket->getBucket();
        $this->assertEquals(20, $bucketArray['count'], 'Token Count after not consumed failed');
    }
}
