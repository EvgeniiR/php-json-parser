<?php

declare(strict_types=1);

namespace JsonParser;

include __DIR__ . '/../vendor/autoload.php';

$res = runParser(jsonValue(), '    "hello, world "   ');

print_r($res);
