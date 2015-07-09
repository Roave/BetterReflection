# Test suites

## General

Running just `vendor/bin/phpunit` will run both the `unit` and the `compat`
test suites, which is expected behaviour.

### unit

The unit test suite covers the library unit implementation. The unit tests can
be executed by simply running (`--colors` is optional):

```php
vendor/bin/phpunit --test-suite unit --colors
```

### core

These are the unmodified core reflection .phpt tests that verify functionality
of the core reflection API. They are provided only for information/comparison
purposes and can be run simply like this:

```php
vendor/bin/phpunit test/core
```

### compat

These are adapted versions of the core reflection .phpt tests that have been
modified to use Better Reflection instead of core reflection. The idea is that
we are trying to maintain compatibility with core API. Ideally, these should
pass (where they pass in core, at least).

```php
vendor/bin/phpunit --test-suite compat test/compat
```
