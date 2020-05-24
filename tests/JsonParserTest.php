<?php

declare(strict_types=1);

use JsonParser\Res;
use PHPUnit\Framework\TestCase;
use function JsonParser\jsonValue;
use function JsonParser\runParser;

class JsonParserTest extends TestCase
{
    public function testParseComplexObject(): void
    {
        $this->runParserAndAssertResult(
            ["hello, world", 'hello, world 2', false, true, null, 3, 123.45, 123e5, 123e-5],
            '["hello, world", "hello, world 2", false, true, null, 3, 123.45, 123e5, 123e-5]'
        );
    }

    public function testParseInt(): void
    {
        $this->runParserAndAssertResult(1, '1.0');
    }

    /** @param mixed $expectedValue */
    private function runParserAndAssertResult($expectedValue, string $parserInput): void {
        $parserResult = runParser(jsonValue(), $parserInput);

        if($parserResult === null) {
            $this->fail(sprintf('Parsing failed on input: "%s"', $parserInput));
        }

        $this->assertResultValueAndEmptyRest($expectedValue, $parserResult);
    }

    /** @param mixed $expectedValue */
    private function assertResultValueAndEmptyRest($expectedValue, Res $a): void {
        $this->assertEquals($expectedValue, $a->a, 'Parsing result does not match the expected result');
        $this->assertEquals('', $a->rest, 'Parser did not parse string entirely');
    }
}