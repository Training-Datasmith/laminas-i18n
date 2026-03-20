<?php

declare (strict_types=1);
namespace Laminas\I18n\Validator;

use function date_default_timezone_get;
use function intl_is_failure;
use Intl_Date_Formatter;
use Intl_Exception;
use function is_string;
use Laminas\Validator\Abstract_Validator;
use Laminas\Validator\Exception as ValidatorException;
use Locale;
/** @final */
class DateTime extends Abstract_Validator
{
    public const INVALID = 'datetimeInvalid';
    public const INVALID_DATETIME = 'datetimeInvalidDateTime';
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $message_templates = [self::INVALID => 'Invalid type given. String expected', self::INVALID_DATETIME => 'The input does not appear to be a valid datetime'];
    /**
     * Optional locale
     *
     * @var string|null
     */
    protected $locale;
    protected int $date_type;
    protected int $time_type;
    /**
     * Optional timezone
     */
    protected string $timezone;
    /** @var string|null */
    protected $pattern;
    protected int $calendar;
    /** @var IntlDateFormatter|null */
    protected $formatter;
    /**
     * Is the formatter invalidated
     * Invalidation occurs when immutable properties are changed
     *
     * @var bool
     */
    protected $invalidate_formatter = false;
    /**
     * Constructor for the Date validator
     *
     * @param iterable<string, mixed> $options
     */
    public function __construct($options = [])
    {
        // Delaying initialization until we know ext/intl is available
        $this->date_type = Intl_Date_Formatter::NONE;
        $this->time_type = Intl_Date_Formatter::NONE;
        $this->calendar = Intl_Date_Formatter::GREGORIAN;
        parent::__construct($options);
        if (null === $this->locale) {
            $this->locale = Locale::get_default();
        }
        if (null === $this->timezone) {
            $this->timezone = date_default_timezone_get();
        }
    }
    /**
     * Sets the calendar to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param int|null $calendar
     * @return $this
     */
    public function set_calendar($calendar)
    {
        $this->calendar = $calendar;
        return $this;
    }
    /**
     * Returns the calendar to by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return int|null
     */
    public function get_calendar()
    {
        if ($this->formatter && !$this->invalidate_formatter) {
            return $this->get_intl_date_formatter()->get_calendar();
        }
        return $this->calendar;
    }
    /**
     * Sets the date format to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param int|null $dateType
     * @return $this
     */
    public function set_date_type($date_type)
    {
        $this->date_type = $date_type;
        $this->invalidate_formatter = true;
        return $this;
    }
    /**
     * Returns the date format used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return int|null
     */
    public function get_date_type()
    {
        return $this->date_type;
    }
    /**
     * Sets the pattern to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param string|null $pattern
     * @return $this
     */
    public function set_pattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }
    /**
     * Returns the pattern used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string|null
     */
    public function get_pattern()
    {
        if ($this->formatter && !$this->invalidate_formatter) {
            return $this->get_intl_date_formatter()->get_pattern();
        }
        return $this->pattern;
    }
    /**
     * Sets the time format to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param int|null $timeType
     * @return $this
     */
    public function set_time_type($time_type)
    {
        $this->time_type = $time_type;
        $this->invalidate_formatter = true;
        return $this;
    }
    /**
     * Returns the time format used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return int|null
     */
    public function get_time_type()
    {
        return $this->time_type;
    }
    /**
     * Sets the timezone to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param string|null $timezone
     * @return $this
     */
    public function set_timezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }
    /**
     * Returns the timezone used by the IntlDateFormatter or the system default if none given
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string|null
     */
    public function get_timezone()
    {
        if ($this->formatter && !$this->invalidate_formatter) {
            return $this->get_intl_date_formatter()->get_time_zone_id();
        }
        return $this->timezone;
    }
    /**
     * Sets the locale to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param string|null $locale
     * @return $this
     */
    public function set_locale($locale)
    {
        $this->locale = $locale;
        $this->invalidate_formatter = true;
        return $this;
    }
    /**
     * Returns the locale used by the IntlDateFormatter or the system default if none given
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string|null
     */
    public function get_locale()
    {
        return $this->locale;
    }
    /**
     * Returns true if and only if $value is a floating-point value
     *
     * @param  string $value
     * @return bool
     * @throws ValidatorException\InvalidArgumentException
     */
    public function is_valid($value)
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $this->set_value($value);
        try {
            $formatter = $this->get_intl_date_formatter();
            if (intl_is_failure($formatter->get_error_code())) {
                throw new Validator_Exception\InvalidArgumentException($formatter->get_error_message());
            }
        } catch (Intl_Exception $intl_exception) {
            throw new Validator_Exception\InvalidArgumentException($intl_exception->get_message(), 0, $intl_exception);
        }
        try {
            $timestamp = $formatter->parse($value);
            if (intl_is_failure($formatter->get_error_code()) || $timestamp === false) {
                $this->error(self::INVALID_DATETIME);
                $this->invalidate_formatter = true;
                return false;
            }
        } catch (Intl_Exception) {
            $this->error(self::INVALID_DATETIME);
            $this->invalidate_formatter = true;
            return false;
        }
        return true;
    }
    /**
     * Returns a non lenient configured IntlDateFormatter
     *
     * @return IntlDateFormatter
     */
    protected function get_intl_date_formatter()
    {
        if ($this->formatter === null || $this->invalidate_formatter) {
            $this->formatter = new Intl_Date_Formatter($this->get_locale(), $this->get_date_type(), $this->get_time_type(), $this->timezone, $this->calendar, $this->pattern ?? '');
            $this->formatter->set_lenient(false);
            $this->set_timezone($this->formatter->get_timezone());
            $this->set_calendar($this->formatter->get_calendar());
            $this->set_pattern($this->formatter->get_pattern());
            $this->invalidate_formatter = false;
        }
        return $this->formatter;
    }
}