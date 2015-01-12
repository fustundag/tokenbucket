# TokenBucket
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
$settings = array(
    'capacity' => 20,
    'fillRate' => 5
);

// Create the bucket
$bucket = new TokenBucket('key-for-bucket', $storage, $settings);

// Check if token is avaible
if ($bucket->consume()===false) {
    //Not allowed!!
    exit;
}

// ...
```

## Contributing
You can contribute by forking the repo and creating pull requests. You can also create issues or feature requests.

## License
This project is licensed under the MIT license. `LICENSE` file can be found in this repository.