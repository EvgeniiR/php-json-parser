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
 * @template T
 *
 * @param Closure(string): ?Res<T> $closure
 * @psalm-return null|Res<T>
 */
function runParser(Closure $closure, string $inp): ?Res {
   return $closure($inp);
}

/**
 * @return Closure(string): ?Res<mixed>
 */
function jsonValue(): Closure {
    return left(
        right(
            ws(),
            oneOf(jsonNull(), jsonBool(), jsonString(), jsonArray(), jsonNumber())
        ),
        ws()
    );
}

/**
 * @return Closure(string): ?Res<null>
 */
function jsonNull(): Closure
{
    return function (string $inp): ?Res {
        return inject(null, stringP('null')) ($inp);
    };
}

/**
 * @return Closure(string): ?Res<bool>
 */
function jsonBool(): Closure {
    return oneOf(
        inject(true, stringP('true')),
        inject(false, stringP('false'))
    );
}

/**
 * @return Closure(string): ?Res<string>
 */
function jsonString(): Closure {
    return stringLiteral();
}

/**
 * @template T
 *
 * @return Closure(string): ?Res<list<T>>
 */
function jsonArray(): Closure {
    $elements = sepBy(
        left(
            right(
                ws(),
                charP(',')
            ),
            ws()
        ),
        fn(string $inp) => jsonValue() ($inp)
    );

    return right(
        right(
            charP('['),
            ws()
        ),
        left(
            left(
                $elements,
                ws()
            ),
            charP(']')
        )
    );
}

/**
 * @template T
 *
 * @return Closure(string): ?Res<float>
 */
function jsonNumber(): Closure {
    return function (string $inp): ?Res {
        $minus = inject(-1, charP('-'));
        $digits = spanP(fn(string $ch) => ctype_digit($ch));
        $plus = inject(1, charP('+'));
        $e = oneOf(charP('e'), charP('E'));

        $sign = $minus ($inp);
        $inp = $sign === null ? $inp : $sign->rest;

        $integral = notNull($digits) ($inp);
        if($integral === null) {
            return null;
        }
        $inp = $integral->rest;

        $dot = charP('.')($inp);
        $inp = $dot === null ? $inp : $dot->rest;

        $decimalPartDigits = notNull($digits);

        $decimalPart = $dot !== null ?
            apply(
                fn(string $str) => $dot->a . $str,
                $decimalPartDigits
            ) ($inp) :
            null;

        $inp = $decimalPart === null ? $inp : $decimalPart->rest;

        $exponent = right(
            $e,
            applicativeApply(
                function (string $input) use ($plus, $minus): Res {
                    $res = oneOf($plus, $minus, fn(string $inp) => new Res($inp, 1)) ($input);

                    if ($res === null) {
                        throw new \RuntimeException('Unexepected null');
                    }

                    return new Res($res->rest, fn(string $digits) => (int)$digits * $res->a);
                },
                $digits
            )
        ) ($inp);

        $inp = $exponent === null ? $inp : $exponent->rest;

        return new Res(
            $exponent !== null ? $exponent->rest : $inp,
            numberFromParts(
                $sign !== null ? $sign->a : 1,
                (int)($integral->a),
                $decimalPart !== null ? (float)$decimalPart->a : 0.0,
                $exponent !== null ? $exponent->a : 0
            )
        );
    };
}

function numberFromParts(
    int $sign,
    int $integral,
    float $decimalPart,
    int $exponent
): float {
    return (float)$sign * ((float)$integral + $decimalPart) * (float)(pow(10, $exponent));
}

/**
 * @template T of (array|string)
 *
 * @param Closure(string): ?Res<T> $parser
 *
 * @return Closure(string): ?Res<T>
 */
function notNull($parser): Closure {
    return function (string $inp) use ($parser): ?Res {
        $res = $parser($inp);

        if($res === null) {
            return null;
        }

        if($res->a === '' || $res->a === []) {
            return null;
        }

        return $res;
    };
}

/**
 * @template T
 * @template U
 *
 * @param Closure(string): ?Res<U> $sep
 * @param Closure(string): ?Res<T> $elParser
 * @return Closure(string): Res<list<T>>
 */
function sepBy(Closure $sep, Closure $elParser): Closure {
    return function (string $inp) use ($sep, $elParser): Res {
        $res = [];

        $iterationRes = $elParser($inp);

        if($iterationRes === null) {
            return new Res($inp, []);
        }

        $res[] = is_scalar($iterationRes->a) ? $iterationRes->a : (clone $iterationRes)->a;
        $rest = $iterationRes->rest;

        while( ($iterationRes = right($sep, $elParser)($iterationRes->rest)) != null) {
            $res[] = is_scalar($iterationRes->a) ? $iterationRes->a : (clone $iterationRes)->a;
            $rest = $iterationRes->rest;
        }

        return new Res($rest, $res);
    };
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
        $a = '';
        $rest = '';
        $continue = true;
        /** @var string $ch */
        foreach (mb_str_split($inp) as $ch) {
            if($continue && $span($ch)) {
                $a .= $ch;
            } else {
                $rest .= $ch;
                $continue = false;
            }
        }
        return new Res($rest, $a);
    };
}