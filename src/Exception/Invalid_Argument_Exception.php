<?php

declare (strict_types=1);
namespace Laminas\I18n\Exception;

use function sprintf;
/** @final */
class InvalidArgumentException extends \InvalidArgumentException implements Exception_Interface
{
    /** @psalm-pure */
    public static function with_invalid_country_code(string $received): self
    {
        return new self(sprintf('Country codes should be 2 letter ISO 3166 strings, received "%s"', $received));
    }
    /** @psalm-pure */
    public static function with_unknown_country_code(string $code): self
    {
        return new self(sprintf('The country code "%s" does not correspond to a known country', $code));
    }
    /** @psalm-pure */
    public static function with_unrecognizable_locale_string(string $locale): self
    {
        return new self(sprintf('The string "%s" could not be parsed as a valid locale', $locale));
    }
    /** @psalm-pure */
    public static function with_undetectable_country_code(string $locale_or_code): self
    {
        return new self(sprintf('The string "%s" could not be understood as either a locale or an ISO 3166 country code', $locale_or_code));
    }
}