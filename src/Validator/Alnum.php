<?php

declare (strict_types=1);
namespace Laminas\I18n\Validator;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use Laminas\I18n\Filter\Alnum as AlnumFilter;
use Laminas\Validator\Abstract_Validator;
/** @final */
class Alnum extends Abstract_Validator
{
    public const INVALID = 'alnumInvalid';
    public const NOT_ALNUM = 'notAlnum';
    public const STRING_EMPTY = 'alnumStringEmpty';
    /**
     * Alphanumeric filter used for validation
     *
     * @var AlnumFilter|null
     */
    protected static $filter;
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $message_templates = [self::INVALID => 'Invalid type given. String, integer or float expected', self::NOT_ALNUM => 'The input contains characters which are non alphabetic and no digits', self::STRING_EMPTY => 'The input is an empty string'];
    /**
     * Options for this validator
     *
     * @var array<string, mixed>
     */
    protected $options = ['allowWhiteSpace' => false];
    /**
     * Sets default option values for this instance
     *
     * @param array{allowWhiteSpace: bool}|bool $allowWhiteSpace
     */
    public function __construct($allow_white_space = false)
    {
        $options = is_array($allow_white_space) ? $allow_white_space : null;
        parent::__construct($options);
        if (is_scalar($allow_white_space)) {
            $this->options['allowWhiteSpace'] = (bool) $allow_white_space;
        }
    }
    /**
     * Returns the allowWhiteSpace option
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return bool
     */
    public function get_allow_white_space()
    {
        return is_bool($this->options['allowWhiteSpace']) && $this->options['allowWhiteSpace'];
    }
    /**
     * Sets the allowWhiteSpace option
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param bool $allowWhiteSpace
     * @return $this
     */
    public function set_allow_white_space($allow_white_space)
    {
        $this->options['allowWhiteSpace'] = (bool) $allow_white_space;
        return $this;
    }
    /**
     * Returns true if and only if $value contains only alphabetic and digit characters
     *
     * @param mixed $value
     * @return bool
     */
    public function is_valid($value)
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $this->set_value($value);
        if ('' === $value) {
            $this->error(self::STRING_EMPTY);
            return false;
        }
        if (null === static::$filter) {
            static::$filter = new Alnum_Filter();
        }
        static::$filter->set_allow_white_space($this->get_allow_white_space());
        if ($value != static::$filter->filter($value)) {
            // phpcs:ignore
            $this->error(self::NOT_ALNUM);
            return false;
        }
        return true;
    }
}