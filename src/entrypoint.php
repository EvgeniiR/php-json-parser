<?php

declare(strict_types=1);

namespace JsonParser;

include __DIR__ . '/../vendor/autoload.php';

$res = runParser(jsonValue(), '["hello, world", "hello, world 2"]');

print_r($res);
