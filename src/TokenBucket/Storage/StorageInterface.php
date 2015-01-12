<?php

/**
 * Copyright (c) Fatih Ustundag <fatih.ustundag@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TokenBucket\Storage;

interface StorageInterface
{
    public function getStorageName();

    /**
     * Gets a value that belongs to a given $key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Sets a given $value in a container given by $key.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl   The time to live for the value (in seconds)
     *
     * @return void
     */
    public function set($key, $value, $ttl = 0);

    /**
     * Deletes an entry from storage.
     *
     * @param string $key
     *
     * @return void
     */
    public function delete($key);
}
