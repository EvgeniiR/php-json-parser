<?php

declare(strict_types=1);

namespace JsonParser;

use Closure;

/**
 * @template T
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
 * @template T
 *
 * @param Closure(string): ?Res<T> $a
 * @param Closure(string): ?Res<T> $b
 * @return Closure(string): ?Res<T>
 */
function right(Closure $a, Closure $b): Closure {
    return function (string $inp) use ($a, $b): ?Res {
        /** @var Res|null $aRes */
        $aRes = $a($inp);

        if($aRes === null) {
            $bInp = $inp;
        } else {
            $bInp = $aRes->rest;
        }

        return $b($bInp);
    };
}

/**
 * @template T
 *
 * @param Closure(string): ?Res<T> $a
 * @param Closure(string): ?Res<T> $b
 * @return Closure(string): ?Res<T>
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