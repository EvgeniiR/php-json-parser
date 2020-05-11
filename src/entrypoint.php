<?php

declare(strict_types=1);

namespace JsonParser;

include __DIR__ . '/../vendor/autoload.php';

$res = runParser(jsonValue(), '["hello, world", "hello, world 2", false, true, null, 3, 123.45, 123e5, 123e-5]');

var_dump($res);
