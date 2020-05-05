<?php

declare(strict_types=1);

namespace JsonParser;

include __DIR__ . '/../vendor/autoload.php';

$res = runParser(jsonValue(), "    true   ");

print_r($res);
