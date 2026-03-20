<?php

declare (strict_types=1);
namespace Laminas\I18n\Exception;

use DomainException;
/** @final */
class Extension_Not_Loaded_Exception extends DomainException implements Exception_Interface
{
}