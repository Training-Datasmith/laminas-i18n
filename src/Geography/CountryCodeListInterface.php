<?php

declare (strict_types=1);
namespace Laminas\I18n\Geography;

use Countable;
use IteratorAggregate;
use Laminas\I18n\Country_Code;
/**
 * @extends IteratorAggregate<array-key, CountryCode>
 */
interface Country_Code_List_Interface extends IteratorAggregate, Countable
{
}