A little experiment, trying to use approaches from https://github.com/EvgeniiR/json-parser in php, in order to clearly find out what might be missing in PHP and other languages with C-like syntax and different type systems in such a task

I gave up trying to implement `jsonNumber` in declarative style, because there is no way to implement Functot.apply / Applicative.apply(`<*>` and `<$>`) which can be combined with currying and partial application.

The json parser is almost ready, there may be some flaws, but the only feature not implemented is parsing escaped symbols
