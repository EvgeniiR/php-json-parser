<?php

declare(strict_types=1);

namespace JsonParser;

use Closure;

/**
 * @template-covariant T
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


        if($aRes === null) {
            return null;
        }

        return $b($aRes->rest);
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
 * @param array<Closure(string): ?Res<T>> $parsers
 * @return Closure(string): ?Res<T>
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

/**
 * @template T
 *
 * @psalm-param T $valueToInject
 * @param mixed $valueToInject
 * @param Closure(string): ?Res<mixed> $functor
 *
 * @return Closure(string): ?Res<T>
 */
function inject($valueToInject, Closure $functor): Closure {
    return function (string $inp) use ($valueToInject, $functor): ?Res {
        $res = $functor ($inp);

        if($res === null) {
            return null;
        }

        return new Res($res->rest, $valueToInject);
    };
}

/**
 * @template A
 * @template B
 *
 * @param Closure(A): B $functor
 * @param Closure(string): ?Res<A> $b
 *
 * @return Closure(string): ?Res<B>
 */
function apply(Closure $functor, Closure $b): Closure {
    return function (string $inp) use ($functor, $b): ?Res {
        $bRes = $b ($inp);

        if($bRes === null) {
            return null;
        }

        return new Res($bRes->rest, $functor($bRes->a));
    };
}

/**
 * @template A
 * @template B
 *
 * @param Closure(string): Res<Closure(A): B> $a
 * @param Closure(string): ?Res<A> $b
 *
 * @return Closure(string): ?Res<B>
 */
function applicativeApply(Closure $a, Closure $b): Closure {
    return function (string $inp) use ($a, $b): ?Res {
        $aRes = $a ($inp);
        $func = $aRes->a;

        $bRes = $b ($aRes->rest);
        if($bRes === null) {
            return null;
        }

        return new Res($bRes->rest, $func($bRes->a));
    };
}

/**
 * @template TArg1
 * @template TArg2
 * @template TRes
 * @template T as (Closure(): TRes | Closure(TArg1): TRes | Closure(TArg1, TArg2): TRes)
 *
 * @psalm-param T $fn
 *
 * @psalm-return (
 *   T is (Closure(TArg1, TArg2): TRes)
 *   ? (Closure(TArg1): (Closure(TArg2): (Closure(): TRes)))
 *   : (
 *       T is (Closure(TArg1): TRes)
 *       ? (Closure(TArg1): (Closure(): TRes))
 *       : (
 *           T is (Closure(): TRes)
 *           ? (Closure(): TRes)
 *           : bool
 *         )
 *     )
 * )
 */
function curry(Closure $fn) {
    $arity = (new \ReflectionFunction($fn))->getNumberOfRequiredParameters();

    if($arity === 0) {
        /** @var Closure(): TRes $fn */
        return fn() => $fn();
    }

    if($arity === 1) {
        /** @var Closure(TArg1): TRes $fn */
        return
            /** @psalm-param TArg1 $arg */
            function($arg) use ($fn) {
                return curry($fn)($arg);
            };
    }

    if($arity === 2) {
        /** @var Closure(TArg1, TArg2): TRes $fn */
        return
            /** @psalm-param TArg1 $arg */
            function($arg) use ($fn) {
                return curry($fn)($arg);
            };
    }

    throw new \RuntimeException();
}
