# TokenBucket [![Build Status](https://travis-ci.org/fustundag/tokenbucket.svg?branch=master)](https://travis-ci.org/fustundag/tokenbucket)
TokenBucket is an algorithm for rate limit

You can check algorithm from http://en.wikipedia.org/wiki/Token_bucket

## Usage

### Basic usage
``` php
<?php

use TokenBucket\TokenBucket;
use TokenBucket\Storage\Memcached as MemcachedStorage;

$storage = new MemcachedStorage();

// Define the bucket
$options = array(
    'capacity' => 20,
    'fillRate' => 5
);

// Create the bucket
$bucket = new TokenBucket('key-for-bucket', $storage, $options);

// Check if token is avaible
if ($bucket->consume()===false) {
    //Not allowed!!
    exit;
}

// ...
```

### Options

1. **capacity** : Max token count of bucket.
2. **fillRate** : Token count to fill per second. For example  if **capacity** is 20 and **fillRate** is 5, 5 tokens will added to bucket every second. But, total token cannot be exceed 20.
3. **ttl**      : Time to live for bucket in seconds. If not given or zero given, it will be calculated automatically according to **capacity** and **fillRate**. **ttl** can be used to reset bucket with **capacity**. For example: if **capacity** is 100 and **fillRate** is zero and ttl is 300, 100 token can be consumed at 300 seconds. After 300 seconds, bucket will be reset to **capacity**. 

## Contributing
You can contribute by forking the repo and creating pull requests. You can also create issues or feature requests.

## License
This project is licensed under the MIT license. `LICENSE` file can be found in this repository.