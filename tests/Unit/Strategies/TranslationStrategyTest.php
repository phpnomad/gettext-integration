<?php

namespace PHPNomad\Gettext\Tests\Unit\Strategies;

use Mockery;
use PHPNomad\Gettext\Strategies\TranslationStrategy;
use PHPNomad\Gettext\Tests\TestCase;
use PHPNomad\Translations\Interfaces\HasLanguage;
use PHPNomad\Translations\Interfaces\HasTextDomain;

class TranslationStrategyTest extends TestCase
{
    private HasTextDomain $textDomainProvider;
    private HasLanguage $languageProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->textDomainProvider = Mockery::mock(HasTextDomain::class);
        $this->textDomainProvider->shouldReceive('getTextDomain')->andReturn('my-domain')->byDefault();

        $this->languageProvider = Mockery::mock(HasLanguage::class);
        $this->languageProvider->shouldReceive('getLanguage')->andReturn(null)->byDefault();
    }

    /**
     * Creates a partial mock of TranslationStrategy that overrides the
     * gettext wrapper methods, allowing tests to run without ext-gettext.
     *
     * @return TranslationStrategy|Mockery\MockInterface
     */
    private function createStrategy(): TranslationStrategy
    {
        $strategy = Mockery::mock(TranslationStrategy::class, [$this->textDomainProvider, $this->languageProvider])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Default: setLocale is a no-op
        $strategy->shouldReceive('setLocale')->byDefault();

        return $strategy;
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translate
     */
    public function testTranslateCallsDgettextWithDomainAndText(): void
    {
        $strategy = $this->createStrategy();

        $strategy->shouldReceive('dgettext')
            ->once()
            ->with('my-domain', 'Hello')
            ->andReturn('Hola');

        $result = $strategy->translate('Hello');

        $this->assertEquals('Hola', $result);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translate
     */
    public function testTranslateWithContextEncodesUsingEotSeparator(): void
    {
        $strategy = $this->createStrategy();

        $expectedMsgid = "noun\x04Post";

        $strategy->shouldReceive('dgettext')
            ->once()
            ->with('my-domain', $expectedMsgid)
            ->andReturn('Publicacion');

        $result = $strategy->translate('Post', 'noun');

        $this->assertEquals('Publicacion', $result);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translate
     */
    public function testTranslateWithContextFallsBackToOriginalWhenUntranslated(): void
    {
        $strategy = $this->createStrategy();

        $encodedMsgid = "noun\x04Post";

        // When gettext has no translation, it returns the msgid unchanged
        $strategy->shouldReceive('dgettext')
            ->once()
            ->with('my-domain', $encodedMsgid)
            ->andReturn($encodedMsgid);

        $result = $strategy->translate('Post', 'noun');

        $this->assertEquals('Post', $result);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translate
     */
    public function testTranslateWithoutContextDoesNotEncodeEot(): void
    {
        $strategy = $this->createStrategy();

        $strategy->shouldReceive('dgettext')
            ->once()
            ->with('my-domain', 'Hello')
            ->andReturn('Hello');

        $strategy->translate('Hello');
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translatePlural
     */
    public function testTranslatePluralCallsDngettextWithDomainAndForms(): void
    {
        $strategy = $this->createStrategy();

        $strategy->shouldReceive('dngettext')
            ->once()
            ->with('my-domain', '%d item', '%d items', 5)
            ->andReturn('5 elementos');

        $result = $strategy->translatePlural('%d item', '%d items', 5);

        $this->assertEquals('5 elementos', $result);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translatePlural
     */
    public function testTranslatePluralWithContextEncodesUsingEotSeparator(): void
    {
        $strategy = $this->createStrategy();

        $expectedSingular = "cart\x04%d item";
        $expectedPlural = "cart\x04%d items";

        $strategy->shouldReceive('dngettext')
            ->once()
            ->with('my-domain', $expectedSingular, $expectedPlural, 3)
            ->andReturn('3 articulos');

        $result = $strategy->translatePlural('%d item', '%d items', 3, 'cart');

        $this->assertEquals('3 articulos', $result);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translatePlural
     */
    public function testTranslatePluralWithContextFallsBackToSingularWhenUntranslatedAndCountIsOne(): void
    {
        $strategy = $this->createStrategy();

        $encodedSingular = "cart\x04%d item";
        $encodedPlural = "cart\x04%d items";

        // gettext returns the singular encoded form when untranslated and count=1
        $strategy->shouldReceive('dngettext')
            ->once()
            ->with('my-domain', $encodedSingular, $encodedPlural, 1)
            ->andReturn($encodedSingular);

        $result = $strategy->translatePlural('%d item', '%d items', 1, 'cart');

        $this->assertEquals('%d item', $result);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translatePlural
     */
    public function testTranslatePluralWithContextFallsBackToPluralWhenUntranslatedAndCountIsNotOne(): void
    {
        $strategy = $this->createStrategy();

        $encodedSingular = "cart\x04%d item";
        $encodedPlural = "cart\x04%d items";

        // gettext returns the plural encoded form when untranslated and count != 1
        $strategy->shouldReceive('dngettext')
            ->once()
            ->with('my-domain', $encodedSingular, $encodedPlural, 5)
            ->andReturn($encodedPlural);

        $result = $strategy->translatePlural('%d item', '%d items', 5, 'cart');

        $this->assertEquals('%d items', $result);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translatePlural
     */
    public function testTranslatePluralWithoutContextDoesNotEncodeEot(): void
    {
        $strategy = $this->createStrategy();

        $strategy->shouldReceive('dngettext')
            ->once()
            ->with('my-domain', '%d item', '%d items', 1)
            ->andReturn('%d item');

        $strategy->translatePlural('%d item', '%d items', 1);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::applyLocale
     */
    public function testApplyLocaleSetsLocaleFromLanguageProvider(): void
    {
        $this->languageProvider = Mockery::mock(HasLanguage::class);
        $this->languageProvider->shouldReceive('getLanguage')->andReturn('es_ES.UTF-8');

        $strategy = $this->createStrategy();

        $strategy->shouldReceive('setLocale')
            ->once()
            ->with('es_ES.UTF-8');

        $strategy->shouldReceive('dgettext')
            ->andReturn('Hola');

        $strategy->translate('Hello');
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::applyLocale
     */
    public function testApplyLocaleSkipsSetlocaleWhenLanguageIsNull(): void
    {
        $this->languageProvider = Mockery::mock(HasLanguage::class);
        $this->languageProvider->shouldReceive('getLanguage')->andReturn(null);

        $strategy = $this->createStrategy();

        $strategy->shouldReceive('setLocale')->never();

        $strategy->shouldReceive('dgettext')
            ->andReturn('Hello');

        $strategy->translate('Hello');
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translate
     */
    public function testTranslateUsesCorrectTextDomainFromProvider(): void
    {
        $this->textDomainProvider = Mockery::mock(HasTextDomain::class);
        $this->textDomainProvider->shouldReceive('getTextDomain')->andReturn('custom-domain');

        $strategy = $this->createStrategy();

        $strategy->shouldReceive('dgettext')
            ->once()
            ->with('custom-domain', 'Hello')
            ->andReturn('Hello');

        $strategy->translate('Hello');
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translatePlural
     */
    public function testTranslatePluralUsesCorrectTextDomainFromProvider(): void
    {
        $this->textDomainProvider = Mockery::mock(HasTextDomain::class);
        $this->textDomainProvider->shouldReceive('getTextDomain')->andReturn('custom-domain');

        $strategy = $this->createStrategy();

        $strategy->shouldReceive('dngettext')
            ->once()
            ->with('custom-domain', '%d item', '%d items', 2)
            ->andReturn('%d items');

        $strategy->translatePlural('%d item', '%d items', 2);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translatePlural
     */
    public function testTranslatePluralWithContextFallsBackToSingularWhenResultMatchesSingularId(): void
    {
        $strategy = $this->createStrategy();

        $encodedSingular = "ctx\x04one item";
        $encodedPlural = "ctx\x04many items";

        $strategy->shouldReceive('dngettext')
            ->once()
            ->andReturn($encodedSingular);

        $result = $strategy->translatePlural('one item', 'many items', 1, 'ctx');

        $this->assertEquals('one item', $result);
    }

    /**
     * @covers \PHPNomad\Gettext\Strategies\TranslationStrategy::translatePlural
     */
    public function testTranslatePluralWithContextFallsBackToPluralWhenResultMatchesPluralId(): void
    {
        $strategy = $this->createStrategy();

        $encodedSingular = "ctx\x04one item";
        $encodedPlural = "ctx\x04many items";

        $strategy->shouldReceive('dngettext')
            ->once()
            ->andReturn($encodedPlural);

        $result = $strategy->translatePlural('one item', 'many items', 0, 'ctx');

        $this->assertEquals('many items', $result);
    }
}
