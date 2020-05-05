<?php

declare(strict_types=1);

namespace JsonParser;

use Closure;

/**
 * @psalm-immutable
 */
class JsonBool {
    public bool $val;

    public function __construct(bool $val)
    {
        $this->val = $val;
    }
}

/**
 * @param Closure(string): ?Res<string> $closure
 */
function runParser(Closure $closure, string $inp): ?Res {
   return $closure($inp);
}

/**
 * @return Closure(string): ?Res<string>
 */
function jsonValue(): Closure {
    return left(
        right(
            ws(),
            oneOf(jsonNull(), jsonBool(), jsonString())
        ),
        ws()
    );
}

/**
 * @return Closure(string): ?Res<string>
 */
function jsonNull(): Closure
{
    return function (string $inp): ?Res {
        return stringP('null') ($inp);
    };
}

/**
 * @return Closure(string): ?Res<string>
 */
function jsonBool(): Closure {
    return oneOf(stringP('true'), stringP('false'));
}

/**
 * @return Closure(string): ?Res<string>
 */
function jsonString(): Closure {
    return stringLiteral();
}

/**
 * @return Closure(string): ?Res<string>
 */
function charP(string $ch): Closure {
    return function (string $inp) use ($ch): ?Res {
        if(strlen($inp) === 0) {
            return null;
        } elseif($inp[0] === $ch) {
            return new Res(\Safe\substr($inp, 1), $ch);
        }
        return null;
    };
}

/**
 * @return Closure(string): ?Res<string>
 */
function stringP(string $str): Closure {
    /** @var string[] $traversable */
    $traversable = \Safe\mb_str_split($str);

    return function (string $inp) use ($traversable): ?Res {
        $rest = $inp;
        $res = '';
        foreach ($traversable as $char) {
            $charRes = charP($char)($rest);

            if($charRes === null) {
                return null;
            }

            [$rest, $parsed] = [$charRes->rest, $charRes->a];
            $res .= $parsed;
        }
        return new Res($rest, $res);
    };
}

/**
 * @return Closure(string): ?Res<string>
 */
function stringLiteral(): Closure {
    return left(
        right(
            charP('"'),
            spanP(
                fn(string $ch) => ($ch !== '"')
            )
        ),
        charP('"'),
    );
}

/**
 * @return Closure(string): Res<string>
 */
function ws(): Closure {
    return spanP(fn(string $ch) => ctype_space($ch));
}

/**
 * @param Closure(string): bool $span
 * @return Closure(string): Res<string>
 */
function spanP(Closure $span): Closure {
    return function (string $inp) use ($span): Res {
        $rest = '';
        $a = '';
        $continue = true;
        /** @var string $ch */
        foreach (mb_str_split($inp) as $ch) {
            if($continue && $span($ch)) {
                $rest .= $ch;
            } else {
                $a .= $ch;
                $continue = false;
            }
        }
        return new Res($a, $rest);
    };
}