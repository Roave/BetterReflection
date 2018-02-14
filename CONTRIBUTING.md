---
title: Contributing
---

# Contributing

 * Coding standard for the project is [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
 * The project aims to follow most [object calisthenics](https://www.slideshare.net/guilhermeblanco/object-calisthenics-applied-to-php)
 * Any contribution must provide tests for additional introduced conditions
 * Any un-confirmed issue needs a failing test case before being accepted
 * Pull requests must be sent from a new hotfix/feature branch, not from `master`.

## Installation

To install the project and run the tests, you need to clone it first:

```sh
$ git clone git://github.com/Roave/BetterReflection.git
```

You will then need to run a [Composer](https://getcomposer.org/) installation:

```sh
$ cd BetterReflection
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar update
```

## Testing

The PHPUnit version to be used is the one installed as a dev- dependency via composer:

```sh
$ vendor/bin/phpunit
```

Please ensure all new features or conditions are covered by unit tests.

Read more about testing in [test/README.md](https://github.com/Roave/BetterReflection/blob/master/test/README.md).
