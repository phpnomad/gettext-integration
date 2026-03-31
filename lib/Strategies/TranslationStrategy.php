<?php

namespace PHPNomad\Gettext\Strategies;

use PHPNomad\Translations\Interfaces\HasLanguage;
use PHPNomad\Translations\Interfaces\HasTextDomain;
use PHPNomad\Translations\Interfaces\TranslationStrategy as TranslationStrategyInterface;

/**
 * Native PHP gettext implementation of the translation strategy.
 *
 * Uses PHP's built-in gettext extension (dgettext, dngettext).
 * Context is encoded using gettext's msgctxt convention (EOT separator \x04).
 */
class TranslationStrategy implements TranslationStrategyInterface
{
    protected HasTextDomain $textDomainProvider;
    protected HasLanguage $languageProvider;

    public function __construct(HasTextDomain $textDomainProvider, HasLanguage $languageProvider)
    {
        $this->textDomainProvider = $textDomainProvider;
        $this->languageProvider = $languageProvider;
    }

    /**
     * Translate a string, optionally with disambiguation context.
     *
     * When context is provided, encodes it using the msgctxt EOT separator (\x04)
     * before passing to dgettext. If the result equals the encoded msgid (meaning
     * no translation was found), falls back to the original text.
     *
     * @param string $text The source string to translate.
     * @param string|null $context Optional disambiguation context.
     * @return string The translated string, or the original if no translation exists.
     */
    public function translate(string $text, ?string $context = null): string
    {
        $this->applyLocale();
        $domain = $this->textDomainProvider->getTextDomain();

        if ($context !== null) {
            $msgid = "{$context}\x04{$text}";
            $result = $this->dgettext($domain, $msgid);

            return $result === $msgid ? $text : $result;
        }

        return $this->dgettext($domain, $text);
    }

    /**
     * Translate a plural string, optionally with disambiguation context.
     *
     * When context is provided, encodes both singular and plural forms using
     * the msgctxt EOT separator (\x04). If the result matches either encoded
     * form (untranslated), falls back to the appropriate English form.
     *
     * @param string $singular The singular form.
     * @param string $plural The plural form.
     * @param int $count The number used to determine which form to use.
     * @param string|null $context Optional disambiguation context.
     * @return string The translated string with the correct plural form.
     */
    public function translatePlural(string $singular, string $plural, int $count, ?string $context = null): string
    {
        $this->applyLocale();
        $domain = $this->textDomainProvider->getTextDomain();

        if ($context !== null) {
            $singularId = "{$context}\x04{$singular}";
            $pluralId = "{$context}\x04{$plural}";
            $result = $this->dngettext($domain, $singularId, $pluralId, $count);

            return ($result === $singularId || $result === $pluralId)
                ? ($count === 1 ? $singular : $plural)
                : $result;
        }

        return $this->dngettext($domain, $singular, $plural, $count);
    }

    /**
     * Apply the locale from the language provider.
     *
     * Sets LC_MESSAGES to the locale returned by the language provider.
     * No-op if the provider returns null.
     *
     * @see HasLanguage::getLanguage()
     */
    protected function applyLocale(): void
    {
        $locale = $this->languageProvider->getLanguage();

        if ($locale !== null) {
            $this->setLocale($locale);
        }
    }

    /**
     * Wrapper for PHP's dgettext function.
     *
     * Extracted to allow test overrides without requiring the gettext extension.
     *
     * @param string $domain The text domain.
     * @param string $msgid The message ID to translate.
     * @return string The translated string.
     */
    protected function dgettext(string $domain, string $msgid): string
    {
        return \dgettext($domain, $msgid);
    }

    /**
     * Wrapper for PHP's dngettext function.
     *
     * Extracted to allow test overrides without requiring the gettext extension.
     *
     * @param string $domain The text domain.
     * @param string $singular The singular message ID.
     * @param string $plural The plural message ID.
     * @param int $count The count to determine plural form.
     * @return string The translated string.
     */
    protected function dngettext(string $domain, string $singular, string $plural, int $count): string
    {
        return \dngettext($domain, $singular, $plural, $count);
    }

    /**
     * Wrapper for PHP's setlocale function.
     *
     * Extracted to allow test overrides without side effects.
     *
     * @param string $locale The locale string.
     * @return string|false The new locale, or false on failure.
     */
    protected function setLocale(string $locale)
    {
        return setlocale(LC_MESSAGES, $locale);
    }
}
