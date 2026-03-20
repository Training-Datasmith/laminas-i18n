<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Loader;

use function gettype;
use function is_array;
use Laminas\I18n\Exception;
use Laminas\I18n\Translator\Plural\Rule as PluralRule;
use Laminas\I18n\Translator\Text_Domain;
use function sprintf;
/**
 * PHP Memory array loader.
 *
 * @final
 */
class Php_Memory_Array implements Remote_Loader_Interface
{
    /** @param array $messages */
    public function __construct(protected $messages)
    {
    }
    /**
     * Load translations from a remote source.
     *
     * @param  string $locale
     * @param  string $textDomain
     * @throws Exception\InvalidArgumentException
     */
    public function load($locale, $text_domain): \Laminas\I18n\Translator\Text_Domain
    {
        if (!is_array($this->messages)) {
            throw new Exception\InvalidArgumentException(sprintf('Expected an array, but received %s', gettype($this->messages)));
        }
        if (!isset($this->messages[$text_domain])) {
            throw new Exception\InvalidArgumentException(sprintf('Expected textdomain "%s" to be an array, but it is not set', $text_domain));
        }
        if (!isset($this->messages[$text_domain][$locale])) {
            throw new Exception\InvalidArgumentException(sprintf('Expected locale "%s" to be an array, but it is not set', $locale));
        }
        $text_domain = new Text_Domain($this->messages[$text_domain][$locale]);
        if ($text_domain->offsetExists('')) {
            if (isset($text_domain['']['plural_forms'])) {
                $text_domain->set_plural_rule(Plural_Rule::from_string($text_domain['']['plural_forms']));
            }
            unset($text_domain['']);
        }
        return $text_domain;
    }
}