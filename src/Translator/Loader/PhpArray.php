<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Loader;

use function gettype;
use function is_array;
use function is_file;
use function is_readable;
use Laminas\I18n\Exception;
use Laminas\I18n\Translator\Plural\Rule as PluralRule;
use Laminas\I18n\Translator\Text_Domain;
use function sprintf;
use function stream_resolve_include_path;
/**
 * PHP array loader.
 *
 * @final
 */
class Php_Array extends Abstract_File_Loader
{
    /**
     * load(): defined by FileLoaderInterface.
     *
     * @see    FileLoaderInterface::load()
     *
     * @param  string $locale
     * @param  string $filename
     * @throws Exception\InvalidArgumentException
     */
    public function load($locale, $filename): \Laminas\I18n\Translator\Text_Domain
    {
        $resolved_include_path = stream_resolve_include_path($filename);
        $from_include_path = $resolved_include_path !== false ? $resolved_include_path : $filename;
        if (!$from_include_path || !is_file($from_include_path) || !is_readable($from_include_path)) {
            throw new Exception\InvalidArgumentException(sprintf('Could not find or open file %s for reading', $filename));
        }
        $messages = include $from_include_path;
        if (!is_array($messages)) {
            throw new Exception\InvalidArgumentException(sprintf('Expected an array, but received %s', gettype($messages)));
        }
        $text_domain = new Text_Domain($messages);
        if ($text_domain->offsetExists('')) {
            if (isset($text_domain['']['plural_forms'])) {
                $text_domain->set_plural_rule(Plural_Rule::from_string($text_domain['']['plural_forms']));
            }
            unset($text_domain['']);
        }
        return $text_domain;
    }
}