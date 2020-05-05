<?php

declare(strict_types=1);

namespace JsonParser;

use Closure;

/**
 * @template T
 * @psalm-immutable
 */
class Res {
    public string $rest = '';

    /** @psalm-var T */
    public $a;

    /** @psalm-param T $a */
    public function __construct(string $rest, $a)
    {
        $this->rest = $rest;
        $this->a = $a;
    }
}

/**
 * @template TA
 * @template TB
 *
 * @param Closure(string): ?Res<TA> $a
 * @param Closure(string): ?Res<TB> $b
 * @return Closure(string): ?Res<TB>
 */
function right(Closure $a, Closure $b): Closure {
    return function (string $inp) use ($a, $b): ?Res {
        /** @var Res|null $aRes */
        $aRes = $a($inp);

        $bInp = $aRes === null ? $inp : $aRes->rest;

        return $b($bInp);
    };
}

/**
 * @template TA
 * @template TB
 *
 * @param Closure(string): ?Res<TA> $a
 * @param Closure(string): ?Res<TB> $b
 * @return Closure(string): ?Res<TA>
 */
function left(Closure $a, Closure $b): Closure {
    return function (string $inp) use ($a, $b): ?Res {
        /** @var Res|null $aRes */
        $aRes = $a($inp);

        if($aRes === null) {
            return null;
        }

        $bRes = $b($aRes->rest);

        if($bRes === null) {
            return null;
        }

        return new Res($bRes->rest, $aRes->a);
    };
}

/**
 * @template T
 *
 * @psalm-param array<Closure(string): ?Res<T>> $a
 * @return Closure(string): null|Res<T>
 */
function oneOf(Closure ... $parsers): Closure {
    return function (string $inp) use ($parsers): ?Res {
        foreach ($parsers as $parser) {
            if(($res = $parser($inp)) !== null) {
                return $res;
            }
        }
        return null;
    };
}