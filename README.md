# phpnomad/gettext-integration

[![Latest Version](https://img.shields.io/packagist/v/phpnomad/gettext-integration.svg)](https://packagist.org/packages/phpnomad/gettext-integration)
[![Total Downloads](https://img.shields.io/packagist/dt/phpnomad/gettext-integration.svg)](https://packagist.org/packages/phpnomad/gettext-integration)
[![PHP Version](https://img.shields.io/packagist/php-v/phpnomad/gettext-integration.svg)](https://packagist.org/packages/phpnomad/gettext-integration)
[![License](https://img.shields.io/packagist/l/phpnomad/gettext-integration.svg)](https://packagist.org/packages/phpnomad/gettext-integration)

Integrates PHP's built-in gettext extension with `phpnomad/translate`. This package provides a `TranslationStrategy` implementation that delegates to `dgettext` and `dngettext`, applies the locale through `setlocale(LC_MESSAGES, ...)`, and handles gettext's `msgctxt` context convention. If you'd rather not depend on the `ext-gettext` PHP extension, `phpnomad/symfony-translation-integration` implements the same abstraction against Symfony Translation instead.

## Installation

This package requires the `ext-gettext` PHP extension to be installed and enabled.

```bash
composer require phpnomad/gettext-integration
```

## What This Provides

- `PHPNomad\Gettext\Strategies\TranslationStrategy`, which implements `PHPNomad\Translations\Interfaces\TranslationStrategy` using `dgettext` and `dngettext`.
- Locale application from an injected `HasLanguage` provider, applied with `setlocale(LC_MESSAGES, ...)` on every call.
- Context disambiguation via the gettext `msgctxt` convention (EOT separator `\x04`), with fallback to the untranslated source (or the appropriate singular/plural form) when no translation is registered for that context.

## Requirements

- PHP 8.0+
- `ext-gettext` PHP extension
- `phpnomad/translate ^2.0`
- A `HasTextDomain` provider and a `HasLanguage` provider bound in your container (both interfaces ship with `phpnomad/translate`)

## Usage

Bind the `TranslationStrategy` interface from `phpnomad/translate` to the gettext implementation inside your bootstrapper. The strategy is then resolved wherever the interface is injected.

```php
<?php

use PHPNomad\Gettext\Strategies\TranslationStrategy as GettextTranslationStrategy;
use PHPNomad\Translations\Interfaces\TranslationStrategy;

$container->bind(TranslationStrategy::class, GettextTranslationStrategy::class);
```

Catalog files, domain binding, and locale setup are handled by the gettext extension itself. See the PHP manual for `bindtextdomain`, `.mo` file layout, and platform-specific notes on locale availability.

## Documentation

The PHPNomad bootstrapping guide lives at [phpnomad.com](https://phpnomad.com). For gettext catalog setup, see the [PHP manual entry for gettext](https://www.php.net/manual/en/book.gettext.php).

## License

MIT. See [LICENSE](LICENSE).
