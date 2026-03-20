<?php

declare (strict_types=1);
namespace Laminas\I18n\Validator;

use function assert;
use function intl_is_failure;
use Intl_Exception;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use Laminas\Stdlib\Array_Utils;
use Laminas\Stdlib\String_Utils;
use Laminas\Stdlib\String_Wrapper\String_Wrapper_Interface;
use Laminas\Validator\Abstract_Validator;
use Laminas\Validator\Exception;
use Locale;
use Number_Formatter;
use function preg_match;
use function preg_quote;
use function str_replace;
use Traversable;
/** @final */
class Is_Float extends Abstract_Validator
{
    public const INVALID = 'floatInvalid';
    public const NOT_FLOAT = 'notFloat';
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $message_templates = [self::INVALID => 'Invalid type given. String, integer or float expected', self::NOT_FLOAT => 'The input does not appear to be a float'];
    /**
     * Optional locale
     *
     * @var string|null
     */
    protected $locale;
    /**
     * UTF-8 compatible wrapper for string functions
     *
     * @var StringWrapperInterface
     */
    protected $wrapper;
    /**
     * Constructor for the integer validator
     *
     * @param iterable<string, mixed> $options
     */
    public function __construct($options = [])
    {
        $this->wrapper = String_Utils::get_wrapper();
        if ($options instanceof Traversable) {
            $options = Array_Utils::iterator_to_array($options);
        }
        if (isset($options['locale'])) {
            $this->set_locale($options['locale']);
        }
        parent::__construct($options);
    }
    /**
     * Returns the set locale
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string
     */
    public function get_locale()
    {
        if (null === $this->locale) {
            $this->locale = Locale::get_default();
        }
        return $this->locale;
    }
    /**
     * Sets the locale to use
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param string|null $locale
     * @return $this
     */
    public function set_locale($locale)
    {
        $this->locale = $locale;
        return $this;
    }
    /**
     * Returns true if and only if $value is a floating-point value. Uses the formal definition of a float as described
     * in the PHP manual: {@link https://www.php.net/float}
     *
     * @param mixed $value
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function is_valid($value)
    {
        if (!is_scalar($value) || is_bool($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $this->set_value($value);
        if (is_float($value) || is_int($value)) {
            return true;
        }
        if ($value === '') {
            $this->error(self::NOT_FLOAT);
            return false;
        }
        // Need to check if this is scientific formatted string. If not, switch to decimal.
        $formatter = new Number_Formatter($this->get_locale(), Number_Formatter::SCIENTIFIC);
        try {
            if (intl_is_failure($formatter->get_error_code())) {
                throw new Exception\InvalidArgumentException($formatter->get_error_message());
            }
        } catch (Intl_Exception $intl_exception) {
            throw new Exception\InvalidArgumentException($intl_exception->get_message(), 0, $intl_exception);
        }
        if (String_Utils::has_pcre_unicode_support()) {
            $exponential_symbols = '[Ee' . $formatter->get_symbol(Number_Formatter::EXPONENTIAL_SYMBOL) . ']+';
            $search = '/' . $exponential_symbols . '/u';
        } else {
            $exponential_symbols = '[Ee]';
            $search = '/' . $exponential_symbols . '/';
        }
        if (!preg_match($search, $value)) {
            $formatter = new Number_Formatter($this->get_locale(), Number_Formatter::DECIMAL);
        }
        /**
         * @desc There are separator "look-alikes" for decimal and group separators that are more commonly used than the
         *       official unicode character. We need to replace those with the real thing - or remove it.
         */
        $group_separator = $formatter->get_symbol(Number_Formatter::GROUPING_SEPARATOR_SYMBOL);
        $dec_separator = $formatter->get_symbol(Number_Formatter::DECIMAL_SEPARATOR_SYMBOL);
        //NO-BREAK SPACE and ARABIC THOUSANDS SEPARATOR
        if ($group_separator === " ") {
            $value = str_replace(' ', $group_separator, $value);
        } elseif ($group_separator === "٬") {
            //NumberFormatter doesn't have grouping at all for Arabic-Indic
            $value = str_replace(['\'', $group_separator], '', $value);
        }
        //ARABIC DECIMAL SEPARATOR
        if ($dec_separator === "٫") {
            $value = str_replace(',', $dec_separator, $value);
        }
        $group_separator_position = $this->wrapper->strpos($value, $group_separator);
        $dec_separator_position = $this->wrapper->strpos($value, $dec_separator);
        //We have separators, and they are flipped. i.e. 2.000,000 for en-US
        if ($group_separator_position !== false && $dec_separator_position !== false && $group_separator_position > $dec_separator_position) {
            $this->error(self::NOT_FLOAT);
            return false;
        }
        //If we have Unicode support, we can use the real graphemes, otherwise, just the ASCII characters
        $decimal = '[' . preg_quote($dec_separator, '/') . ']';
        $prefix = '[+-]';
        $exp = $exponential_symbols;
        $number_range = '0-9';
        $use_unicode = '';
        $suffix = '';
        if (String_Utils::has_pcre_unicode_support()) {
            $prefix = '[' . preg_quote($formatter->get_text_attribute(Number_Formatter::POSITIVE_PREFIX) . $formatter->get_text_attribute(Number_Formatter::NEGATIVE_PREFIX) . $formatter->get_symbol(Number_Formatter::PLUS_SIGN_SYMBOL) . $formatter->get_symbol(Number_Formatter::MINUS_SIGN_SYMBOL), '/') . ']{0,3}';
            $suffix = $formatter->get_text_attribute(Number_Formatter::NEGATIVE_SUFFIX);
            $suffix = $suffix !== false ? '[' . preg_quote($formatter->get_text_attribute(Number_Formatter::POSITIVE_SUFFIX) . $formatter->get_text_attribute(Number_Formatter::NEGATIVE_SUFFIX) . $formatter->get_symbol(Number_Formatter::PLUS_SIGN_SYMBOL) . $formatter->get_symbol(Number_Formatter::MINUS_SIGN_SYMBOL), '/') . ']{0,3}' : '';
            $number_range = '\p{N}';
            $use_unicode = 'u';
        }
        /**
         * @see https://www.php.net/float
         *
         * @desc Match against the formal definition of a float. The
         *       exponential number check is modified for RTL non-Latin number
         *       systems (Arabic-Indic numbering). I'm also switching out the period
         *       for the decimal separator. The formal definition leaves out +- from
         *       the integer and decimal notations so add that.  This also checks
         *       that a grouping sperator is not in the last GROUPING_SIZE graphemes
         *       of the string - i.e. 10,6 is not valid for en-US.
         */
        $lnum = '[' . $number_range . ']+';
        $dnum = '(([' . $number_range . ']*' . $decimal . $lnum . ')|(' . $lnum . $decimal . '[' . $number_range . ']*))';
        $exp_dnum = '((' . $prefix . '((' . $lnum . '|' . $dnum . ')' . $exp . $prefix . $lnum . ')' . $suffix . ')|' . '(' . $suffix . '(' . $lnum . $prefix . $exp . '(' . $dnum . '|' . $lnum . '))' . $prefix . '))';
        // LEFT-TO-RIGHT MARK (U+200E) is messing up everything for the handful
        // of locales that have it
        $lnum_search = str_replace("‎", '', '/^' . $prefix . $lnum . $suffix . '$/' . $use_unicode);
        $dnum_search = str_replace("‎", '', '/^' . $prefix . $dnum . $suffix . '$/' . $use_unicode);
        $exp_dnum_search = str_replace("‎", '', '/^' . $exp_dnum . '$/' . $use_unicode);
        $value = str_replace("‎", '', $value);
        $un_grouped_value = str_replace($group_separator, '', $value);
        // No strrpos() in wrappers yet. ICU 4.x doesn't have grouping size for
        // everything. ICU 52 has 3 for ALL locales.
        $group_size = $formatter->get_attribute(Number_Formatter::GROUPING_SIZE);
        $group_size = $group_size === false ? 3 : $group_size;
        assert(is_int($group_size));
        $last_string_group = $this->wrapper->strlen($value) > $group_size ? $this->wrapper->substr($value, -$group_size) : $value;
        assert(is_string($last_string_group));
        assert($last_string_group !== '');
        assert($lnum_search !== '');
        assert($dnum_search !== '');
        assert($exp_dnum_search !== '');
        if ((preg_match($lnum_search, $un_grouped_value) || preg_match($dnum_search, $un_grouped_value) || preg_match($exp_dnum_search, $un_grouped_value)) && false === $this->wrapper->strpos($last_string_group, $group_separator)) {
            return true;
        }
        $this->error(self::NOT_FLOAT);
        return false;
    }
}