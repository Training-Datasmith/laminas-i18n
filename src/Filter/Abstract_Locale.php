<?php

declare (strict_types=1);
namespace Laminas\I18n\Filter;

use Laminas\Filter\Abstract_Filter;
use Locale;
/**
 * @psalm-type Options = array{
 *     locale: string|null,
 *     ...
 * }
 * @template TOptions of Options
 * @extends AbstractFilter<TOptions>
 */
abstract class Abstract_Locale extends Abstract_Filter
{
    /**
     * Sets the locale option
     *
     * @param  string|null $locale
     * @return $this
     */
    public function set_locale($locale = null)
    {
        $this->options['locale'] = $locale;
        return $this;
    }
    /**
     * Returns the locale option
     *
     * @return string
     */
    public function get_locale()
    {
        if (!isset($this->options['locale'])) {
            $this->options['locale'] = Locale::get_default();
        }
        return $this->options['locale'];
    }
}