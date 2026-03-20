<?php

declare (strict_types=1);
namespace Laminas\I18n\Filter;

use function in_array;
use function is_array;
use function is_scalar;
use Laminas\Stdlib\String_Utils;
use Locale;
use function preg_replace;
use Traversable;
/**
 * @psalm-type Options = array{
 *     locale: string|null,
 *     allow_white_space: bool,
 *     ...
 * }
 * @extends AbstractLocale<Options>
 */
class Alnum extends Abstract_Locale
{
    /** @var Options */
    protected $options = ['locale' => null, 'allow_white_space' => false];
    /**
     * Sets default option values for this instance
     *
     * @param array|Traversable|bool|null $allowWhiteSpaceOrOptions
     * @param string|null $locale
     */
    public function __construct($allow_white_space_or_options = null, $locale = null)
    {
        parent::__construct();
        if ($allow_white_space_or_options !== null) {
            if (static::is_options($allow_white_space_or_options)) {
                $this->set_options($allow_white_space_or_options);
            } else {
                $this->set_allow_white_space($allow_white_space_or_options);
                $this->set_locale($locale);
            }
        }
    }
    /**
     * Sets the allowWhiteSpace option
     *
     * @param  bool $flag
     * @return $this
     */
    public function set_allow_white_space($flag = true)
    {
        $this->options['allow_white_space'] = (bool) $flag;
        return $this;
    }
    /**
     * Whether white space is allowed
     *
     * @return bool
     */
    public function get_allow_white_space()
    {
        return $this->options['allow_white_space'];
    }
    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Returns $value as string with all non-alphanumeric characters removed
     *
     * @param mixed $value
     * @return string|list<string>|mixed
     */
    public function filter($value)
    {
        if (!is_scalar($value) && !is_array($value)) {
            return $value;
        }
        $white_space = $this->options['allow_white_space'] ? '\s' : '';
        $language = Locale::get_primary_language($this->get_locale());
        if (!String_Utils::has_pcre_unicode_support()) {
            // POSIX named classes are not supported, use alternative a-zA-Z0-9 match
            $pattern = '/[^a-zA-Z0-9' . $white_space . ']/';
        } elseif (in_array($language, ['ja', 'ko', 'zh'], true)) {
            // Use english alphabet
            $pattern = '/[^a-zA-Z0-9' . $white_space . ']/u';
        } else {
            // Use native language alphabet
            $pattern = '/[^\p{L}\p{N}' . $white_space . ']/u';
        }
        $value = is_scalar($value) ? (string) $value : $value;
        return preg_replace($pattern, '', $value);
    }
}