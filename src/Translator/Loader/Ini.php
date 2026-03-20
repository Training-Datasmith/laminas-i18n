<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Loader;

use function array_shift;
use function count;
use function gettype;
use function is_array;
use function is_file;
use function is_readable;
use Laminas\Config\Reader\Ini as IniReader;
use Laminas\I18n\Exception;
use Laminas\I18n\Translator\Plural\Rule as PluralRule;
use Laminas\I18n\Translator\Text_Domain;
use function sprintf;
use function stream_resolve_include_path;
/**
 * PHP INI format loader.
 *
 * @final
 */
class Ini extends Abstract_File_Loader
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
        $messages = [];
        $ini_reader = new Ini_Reader();
        $messages_namespaced = $ini_reader->from_file($from_include_path);
        $list = $messages_namespaced;
        if (isset($messages_namespaced['translation'])) {
            $list = $messages_namespaced['translation'];
        }
        foreach ($list as $message) {
            if (!is_array($message) || count($message) < 2) {
                throw new Exception\InvalidArgumentException('Each INI row must be an array with message and translation');
            }
            if (isset($message['message'], $message['translation'])) {
                $messages[$message['message']] = $message['translation'];
                continue;
            }
            $messages[array_shift($message)] = array_shift($message);
        }
        if (!is_array($messages)) {
            throw new Exception\InvalidArgumentException(sprintf('Expected an array, but received %s', gettype($messages)));
        }
        $text_domain = new Text_Domain($messages);
        if (isset($messages_namespaced['plural']['plural_forms'])) {
            $text_domain->set_plural_rule(Plural_Rule::from_string($messages_namespaced['plural']['plural_forms']));
        }
        return $text_domain;
    }
}