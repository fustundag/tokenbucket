<?php
/**
 * Copyright (c) Fatih Ustundag <fatih.ustundag@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TokenBucket\Storage;

use TokenBucket\Exception\StorageException;
use TokenBucket\Storage\Memcached as MemcachedStorage;

class MemcachedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MemcachedStorage
     */
    private $storage;

    /**
     * @var \Memcached
     */
    protected static $memcached;

    public static function setUpBeforeClass()
    {
        self::$memcached = new \Memcached();
        self::$memcached->setOptions(array(
            \Memcached::OPT_TCP_NODELAY => true,
            \Memcached::OPT_NO_BLOCK => true,
            \Memcached::OPT_CONNECT_TIMEOUT => 1000
        ));
        if (count(self::$memcached->getServerList()) == 0) {
            self::$memcached->addServers(array(array(MEMCACHED_HOST, MEMCACHED_PORT)));
        }
    }

    public static function tearDownAfterClass()
    {
        self::$memcached = null;
    }

    public function setUp()
    {
        self::$memcached->flush();
        self::$memcached->quit();
        self::$memcached->set('found', array('count' => 5, 'time' => strtotime('2015-01-01 00:00:00')));
        self::$memcached->set('oldkey', 'old story');
        $this->storage = new MemcachedStorage(self::$memcached);
    }

    public function tearDown()
    {
        $this->storage = null;
    }

    public function testGetStorageName()
    {
        $this->assertEquals('Memcached', $this->storage->getStorageName(), 'get storage name failed');
    }

    public function testGetCasArray()
    {
        $this->assertEquals(array(), $this->storage->getCasArray(), 'get cas array failed');
    }

    public function testGetResultCode()
    {
        $this->storage->set('abc', '123');
        $this->assertEquals(0, $this->storage->getResultCode(), 'get result code failed');

        $this->storage->get('notfound');
        $this->assertEquals(\Memcached::RES_NOTFOUND, $this->storage->getResultCode(), 'get result code failed');
    }

    /**
     * @group deleteTestSet
     */
    public function testGet()
    {
        $this->assertFalse($this->storage->get('notfound'), '"notfound" key test failed');
        $this->assertEquals(
            array('count' => 5, 'time' => strtotime('2015-01-01 00:00:00')),
            $this->storage->get('found'),
            '"found" key test failed'
        );
        try {
            self::$memcached->resetServerList();
            $this->storage->get('notfound');
        } catch (StorageException $e) {
            if (count(self::$memcached->getServerList()) == 0) {
                self::$memcached->addServers(array(array(MEMCACHED_HOST, MEMCACHED_PORT)));
            }
            return;
        }
        $this->fail('Storage exception failed');
    }

    /**
     * @expectedException \TokenBucket\Exception\StorageException
     * @expectedExceptionMessage [STORAGE] Given value for "Memcached::set" not valid! Key: newkey
     */
    public function testSetValueException()
    {
        $this->storage->set('newkey', false);
    }

    /**
     * @group notest
     * @expectedException \TokenBucket\Exception\StorageException
     * @expectedExceptionMessage [STORAGE] "Memcached::set" failed! StorageRespCode: 9, Key: new key, Cas: null
     */
    public function testSetInvalidKeyException()
    {
        $this->storage->set('new key', array(1,2,3));
    }

    public function testSet()
    {
        $this->assertEquals(0, $this->storage->set('newkey', array(1,2,3)), 'Storage set failed');

        sleep(1);
        $this->assertEquals(0, $this->storage->set('newkey', 'again set'), 'Storage re-set failed');
    }

    public function testDelete()
    {
        $this->storage->get('oldkey');
        $this->assertArrayHasKey('oldkey', $this->storage->getCasArray(), 'Cas array has not key "oldkey"!');
        $this->assertEquals(0, $this->storage->delete('oldkey'), 'Delete failed!');
        $this->assertArrayNotHasKey('oldkey', $this->storage->getCasArray(), 'Cas array has key "oldkey"!');
        try {
            self::$memcached->resetServerList();
            $this->storage->delete('oldkey');
        } catch (StorageException $e) {
            if (count(self::$memcached->getServerList()) == 0) {
                self::$memcached->addServers(array(array(MEMCACHED_HOST, MEMCACHED_PORT)));
            }
            return;
        }
        $this->fail('Storage delete exception test failed');
    }
}
