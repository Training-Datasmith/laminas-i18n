<?php

declare (strict_types=1);
namespace Laminas\I18n\Validator;

use function array_key_exists;
use function intl_is_failure;
use Intl_Exception;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use Laminas\Stdlib\Array_Utils;
use Laminas\Validator\Abstract_Validator;
use Laminas\Validator\Exception;
use Locale;
use Number_Formatter;
use function strtr;
use Traversable;
/** @final */
class Is_Int extends Abstract_Validator
{
    public const INVALID = 'intInvalid';
    public const NOT_INT = 'notInt';
    public const NOT_INT_STRICT = 'notIntStrict';
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $message_templates = [self::INVALID => 'Invalid type given. String or integer expected', self::NOT_INT => 'The input does not appear to be an integer', self::NOT_INT_STRICT => 'The input is not strictly an integer'];
    /**
     * Optional locale
     *
     * @var string|null
     */
    protected $locale;
    /**
     * Data type is not enforced by default, so the string '123' is considered an integer.
     * Setting strict to true will enforce the integer data type.
     *
     * @var bool
     */
    protected $strict = false;
    /**
     * Constructor for the integer validator
     *
     * @param iterable<string, mixed> $options
     */
    public function __construct($options = [])
    {
        if ($options instanceof Traversable) {
            $options = Array_Utils::iterator_to_array($options);
        }
        if (isset($options['locale'])) {
            $this->set_locale($options['locale']);
        }
        if (array_key_exists('strict', $options)) {
            $this->set_strict($options['strict']);
        }
        parent::__construct($options);
    }
    /**
     * Returns the set locale
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string|null
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
     * Returns the strict option
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return bool
     */
    public function get_strict()
    {
        return $this->strict;
    }
    /**
     * Sets the strict option mode
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param bool $strict
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function set_strict($strict)
    {
        if (!is_bool($strict)) {
            throw new Exception\InvalidArgumentException('Strict option must be a boolean');
        }
        $this->strict = $strict;
        return $this;
    }
    /**
     * Returns true if and only if $value is a valid integer
     *
     * @param mixed $value
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function is_valid($value)
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $this->error(self::INVALID);
            return false;
        }
        if (is_int($value)) {
            return true;
        }
        if ($this->strict) {
            $this->error(self::NOT_INT_STRICT);
            return false;
        }
        $this->set_value($value);
        $locale = $this->get_locale();
        try {
            $format = new Number_Formatter($locale, Number_Formatter::DECIMAL);
            if (intl_is_failure($format->get_error_code())) {
                throw new Exception\InvalidArgumentException('Invalid locale string given');
            }
        } catch (Intl_Exception $intl_exception) {
            throw new Exception\InvalidArgumentException('Invalid locale string given', 0, $intl_exception);
        }
        try {
            $parsed_int = $format->parse((string) $value, Number_Formatter::TYPE_INT64);
            if (intl_is_failure($format->get_error_code())) {
                $this->error(self::NOT_INT);
                return false;
            }
        } catch (Intl_Exception) {
            $this->error(self::NOT_INT);
            return false;
        }
        $decimal_sep = $format->get_symbol(Number_Formatter::DECIMAL_SEPARATOR_SYMBOL);
        $grouping_sep = $format->get_symbol(Number_Formatter::GROUPING_SEPARATOR_SYMBOL);
        $value_filtered = strtr((string) $value, [$grouping_sep => '', $decimal_sep => '.']);
        if ((string) $parsed_int !== $value_filtered) {
            $this->error(self::NOT_INT);
            return false;
        }
        return true;
    }
}