<?php

declare (strict_types=1);
namespace Laminas\I18n\Filter;

use function is_float;
use function is_int;
use function is_scalar;
use Laminas\Stdlib\Error_Handler;
/** @final */
class Number_Format extends Number_Parse
{
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
        if (!is_scalar($value)) {
            return $value;
        }
        if (!is_int($value) && !is_float($value)) {
            $result = parent::filter($value);
        } else {
            Error_Handler::start();
            $result = $this->get_formatter()->format($value, $this->get_type());
            Error_Handler::stop();
        }
        if (false !== $result) {
            return $result;
        }
        return $value;
    }
}