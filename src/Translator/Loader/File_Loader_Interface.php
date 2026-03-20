<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Loader;

use Laminas\I18n\Translator\Text_Domain;
/**
 * File loader interface.
 */
interface File_Loader_Interface
{
    /**
     * Load translations from a file.
     *
     * @param  string $locale
     * @param  string $filename
     * @return TextDomain|null
     */
    public function load($locale, $filename);
}