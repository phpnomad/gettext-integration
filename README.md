# phpnomad/gettext-integration

Native PHP gettext implementation of the PHPNomad translation strategy.

## Requirements

- PHP 8.0+
- ext-gettext
- phpnomad/translate ^2.0

## Installation

```bash
composer require phpnomad/gettext-integration
```

## Usage

```php
use PHPNomad\Gettext\Strategies\TranslationStrategy;

$strategy = new TranslationStrategy($textDomainProvider, $languageProvider);

// Simple translation
$strategy->translate('Hello');

// Translation with context
$strategy->translate('Post', 'noun');

// Plural translation
$strategy->translatePlural('%d item', '%d items', $count);

// Plural with context
$strategy->translatePlural('%d item', '%d items', $count, 'cart');
```

## How It Works

- Uses PHP's built-in `dgettext()` and `dngettext()` functions.
- Text domain is resolved from the injected `HasTextDomain` provider.
- Locale is set from the injected `HasLanguage` provider via `setlocale(LC_MESSAGES, ...)`.
- Context is encoded using gettext's `msgctxt` convention (EOT separator `\x04`).
