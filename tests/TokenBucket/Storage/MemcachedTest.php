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

    public function setUp()
    {
        $getMap = array(
            array("notfound", null, null, null, null, false),
            array("found", null, null, null, null,  array('count' => 5, 'time' => strtotime('2015-01-01 00:00:00'))),
        );
        $memcachedMock = $this->getMock('\Memcached');

        $memcachedMock->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $memcachedMock->expects($this->any())
            ->method('getResultCode')
            ->will($this->onConsecutiveCalls(16, 0, 1, 16, 2));

        $this->storage = new MemcachedStorage($memcachedMock);
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

    public function testGet()
    {
        $this->assertFalse($this->storage->get('notfound'), '"notfound" key test failed');
        $this->assertEquals(
            array('count' => 5, 'time' => strtotime('2015-01-01 00:00:00')),
            $this->storage->get('found'),
            '"found" key test failed'
        );
        try {
            $this->storage->get('notfound');
        } catch (StorageException $e) {
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

    public function testSet()
    {
        $this->assertEquals(0, $this->storage->set('newkey', array(1,2,3), 0), 'Storage set failed');
        try {
            $this->storage->getResultCode();
            $this->storage->set('newkey', array(1,2,3), 0);
        } catch (StorageException $e) {
            return;
        }
        $this->fail('Storage set exception failed');
    }

    public function testDelete()
    {
        $this->storage->get('oldkey');
        $this->assertArrayHasKey('oldkey', $this->storage->getCasArray(), 'Cas array has not key "oldkey"!');
        $this->assertEquals(0, $this->storage->delete('oldkey'));
        $this->assertArrayNotHasKey('oldkey', $this->storage->getCasArray(), 'Cas array has key "oldkey"!');
        try {
            $this->storage->delete('oldkey');
        } catch (StorageException $e) {
            return;
        }
        $this->fail('Storage delete exception test failed');
    }
}
