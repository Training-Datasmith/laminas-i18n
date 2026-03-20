<?php

declare (strict_types=1);
namespace Laminas\I18n\Filter;

use function in_array;
use function is_array;
use function is_scalar;
use Laminas\Stdlib\String_Utils;
use Locale;
use function preg_replace;
/** @final */
class Alpha extends Alnum
{
    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Returns the string $value, removing all but alphabetic characters
     *
     * @param mixed $value
     * @return ($value is scalar ? string : ($value is list<scalar> ? list<string> : mixed))
     */
    public function filter($value)
    {
        if (!is_scalar($value) && !is_array($value)) {
            return $value;
        }
        $white_space = $this->options['allow_white_space'] ? '\s' : '';
        $language = Locale::get_primary_language($this->get_locale());
        if (!String_Utils::has_pcre_unicode_support()) {
            // POSIX named classes are not supported, use alternative [a-zA-Z] match
            $pattern = '/[^a-zA-Z' . $white_space . ']/';
        } elseif (in_array($language, ['ja', 'ko', 'zh'], true)) {
            // Use english alphabet
            $pattern = '/[^a-zA-Z' . $white_space . ']/u';
        } else {
            // Use native language alphabet
            $pattern = '/[^\p{L}' . $white_space . ']/u';
        }
        return preg_replace($pattern, '', $value);
    }
}