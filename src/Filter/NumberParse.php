<?php

declare (strict_types=1);
namespace Laminas\I18n\Filter;

use function intl_get_error_message;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function iterator_to_array;
use Laminas\I18n\Exception;
use Laminas\Stdlib\Error_Handler;
use Number_Formatter;
use Traversable;
/**
 * @psalm-type Options = array{
 *    locale: string|null,
 *    style: int,
 *    type: NumberFormatter::TYPE_*,
 *    ...
 * }
 * @extends AbstractLocale<Options>
 */
class Number_Parse extends Abstract_Locale
{
    /** @var Options */
    protected $options = ['locale' => null, 'style' => Number_Formatter::DEFAULT_STYLE, 'type' => Number_Formatter::TYPE_DOUBLE];
    /** @var NumberFormatter|null */
    protected $formatter;
    /**
     * @param array|Traversable|string|null $localeOrOptions
     * @param int $style
     * @param int $type
     * @psalm-param NumberFormatter::TYPE_* $type
     */
    public function __construct($locale_or_options = null, $style = Number_Formatter::DEFAULT_STYLE, $type = Number_Formatter::TYPE_DOUBLE)
    {
        parent::__construct();
        if ($locale_or_options !== null) {
            if ($locale_or_options instanceof Traversable) {
                $locale_or_options = iterator_to_array($locale_or_options);
            }
            if (!is_array($locale_or_options)) {
                $this->set_locale($locale_or_options);
                $this->set_style($style);
                $this->set_type($type);
            } else {
                $this->set_options($locale_or_options);
            }
        }
    }
    /**
     * @param  string|null $locale
     * @return $this
     */
    public function set_locale($locale = null): static
    {
        $this->options['locale'] = $locale;
        $this->formatter = null;
        return $this;
    }
    /**
     * @param  int $style
     * @return $this
     */
    public function set_style($style)
    {
        $this->options['style'] = (int) $style;
        $this->formatter = null;
        return $this;
    }
    /**
     * @return int
     */
    public function get_style()
    {
        return $this->options['style'];
    }
    /**
     * @param int $type
     * @psalm-param NumberFormatter::TYPE_* $type
     * @return $this
     */
    public function set_type($type)
    {
        $this->options['type'] = (int) $type;
        return $this;
    }
    /**
     * @return NumberFormatter::TYPE_*
     */
    public function get_type()
    {
        return $this->options['type'];
    }
    /**
     * @return $this
     */
    public function set_formatter(Number_Formatter $formatter)
    {
        $this->formatter = $formatter;
        return $this;
    }
    /**
     * @return NumberFormatter
     * @throws Exception\RuntimeException
     */
    public function get_formatter()
    {
        if ($this->formatter === null) {
            $formatter = Number_Formatter::create($this->get_locale(), $this->get_style());
            if (!$formatter) {
                throw new Exception\RuntimeException('Can not create NumberFormatter instance; ' . intl_get_error_message());
            }
            $this->formatter = $formatter;
        }
        return $this->formatter;
    }
    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * @see    \Laminas\Filter\FilterInterface::filter()
     *
     * @param  mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        if (!is_scalar($value) || is_bool($value)) {
            return $value;
        }
        if (!is_int($value) && !is_float($value)) {
            Error_Handler::start();
            $result = $this->get_formatter()->parse($value, $this->get_type());
            Error_Handler::stop();
            if (false !== $result) {
                return $result;
            }
        }
        return $value;
    }
}