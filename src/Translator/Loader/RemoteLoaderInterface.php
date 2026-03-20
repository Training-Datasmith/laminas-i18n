<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Loader;

use Laminas\I18n\Translator\Text_Domain;
/**
 * Remote loader interface.
 */
interface Remote_Loader_Interface
{
    /**
     * Load translations from a remote source.
     *
     * @param  string $locale
     * @param  string $textDomain
     * @return TextDomain|null
     */
    public function load($locale, $text_domain);
}