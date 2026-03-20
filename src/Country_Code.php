<?php

declare (strict_types=1);
namespace Laminas\I18n;

use function assert;
use Laminas\I18n\Exception\InvalidArgumentException;
use Locale;
use function preg_match;
use function strtoupper;
/**
 * @psalm-immutable
 */
final readonly class Country_Code
{
    /** @param non-empty-string $code */
    private function __construct(private string $code)
    {
    }
    /** @return non-empty-string */
    public function to_string(): string
    {
        return $this->code;
    }
    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
    /**
     * Create a new ValueObject from an ISO 3166 Country Code
     * Country codes are 2 letter, uppercase strings representing a country identifier on planet earth. The given
     * value must also represent a country known by PHP’s intl extension.
     * Valid values include 'US', 'GB', 'ZA', 'FR' etc.
     *
     * @link https://en.wikipedia.org/wiki/List_of_ISO_3166_country_codes
     *
     * @param non-empty-string $code
     * @throws InvalidArgumentException An invalid string or an unknown country will cause an exception.
     * @psalm-pure
     */
    public static function from_string(string $code): self
    {
        $code = strtoupper($code);
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            throw InvalidArgumentException::with_invalid_country_code($code);
        }
        $display_name = Locale::get_display_region('-' . $code, 'GB');
        if ($display_name === '' || $display_name === 'Unknown Region') {
            throw InvalidArgumentException::with_unknown_country_code($code);
        }
        return new self($code);
    }
    /**
     * Create a new value object from a locale string
     *
     * Given a well-formed locale, this method will extract the relevant country code and proxy to @link fromString
     * Valid values include: 'en_GB', 'en-GB', 'zh-Hans-CN'
     *
     * @param non-empty-string $locale
     * @throws InvalidArgumentException An unrecognizable locale will cause an exception.
     * @psalm-pure
     */
    public static function from_locale_string(string $locale): self
    {
        $region = Locale::get_region($locale);
        if ($region === null || $region === '') {
            throw InvalidArgumentException::with_unrecognizable_locale_string($locale);
        }
        return self::from_string($region);
    }
    /**
     * Return a country code from either a string code or a locale string falling back to the system locale if null
     *
     * @link fromLocaleString
     * @link fromString
     *
     * @throws InvalidArgumentException When a non-empty string is provided that cannot be recognized,
     *                                  an exception will be thrown.
     */
    public static function detect(string|self|null $country_code_or_locale = null): self
    {
        if ($country_code_or_locale instanceof self) {
            return $country_code_or_locale;
        }
        if ($country_code_or_locale === null || $country_code_or_locale === '') {
            $country_code_or_locale = Locale::get_default();
        }
        assert($country_code_or_locale !== '');
        $code = self::try_from_string($country_code_or_locale);
        if ($code) {
            return $code;
        }
        throw InvalidArgumentException::with_undetectable_country_code($country_code_or_locale);
    }
    /**
     * Attempt to create a value object from either a country code or a locale string
     *
     * This method returns null if the input cannot be recognized as either a code or a locale.
     *
     * @link fromLocaleString
     * @link fromString
     *
     * @param non-empty-string $countryCodeOrLocale
     * @psalm-pure
     */
    public static function try_from_string(string $country_code_or_locale): ?self
    {
        try {
            return self::from_locale_string($country_code_or_locale);
        } catch (InvalidArgumentException) {
        }
        try {
            return self::from_string($country_code_or_locale);
        } catch (InvalidArgumentException) {
        }
        return null;
    }
}