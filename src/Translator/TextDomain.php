<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator;

use function array_replace;
use ArrayObject;
use Laminas\I18n\Exception;
use Laminas\I18n\Translator\Plural\Rule as PluralRule;
/**
 * Text domain.
 *
 * @template TKey of array-key
 * @template TValue
 * @extends ArrayObject<TKey, TValue>
 * @final
 */
class Text_Domain extends ArrayObject
{
    /**
     * Plural rule.
     *
     * @var PluralRule|null
     */
    protected $plural_rule;
    /**
     * Default plural rule shared between instances.
     *
     * @var PluralRule|null
     */
    protected static $default_plural_rule;
    /**
     * Set the plural rule
     *
     * @return $this
     */
    public function set_plural_rule(Plural_Rule $rule): static
    {
        $this->plural_rule = $rule;
        return $this;
    }
    /**
     * Get the plural rule.
     *
     * @param  bool $fallbackToDefaultRule
     * @return PluralRule|null
     */
    public function get_plural_rule($fallback_to_default_rule = true)
    {
        if ($this->plural_rule === null && $fallback_to_default_rule) {
            return static::get_default_plural_rule();
        }
        return $this->plural_rule;
    }
    /**
     * Checks whether the text domain has a plural rule.
     */
    public function has_plural_rule(): bool
    {
        return $this->plural_rule !== null;
    }
    /**
     * Returns a shared default plural rule.
     *
     * @return PluralRule
     */
    public static function get_default_plural_rule()
    {
        if (static::$default_plural_rule === null) {
            static::$default_plural_rule = Plural_Rule::from_string('nplurals=2; plural=n != 1;');
        }
        return static::$default_plural_rule;
    }
    /**
     * Merge another text domain with the current one.
     *
     * The plural rule of both text domains must be compatible for a successful
     * merge. We are only validating the number of plural forms though, as the
     * same rule could be made up with different expression.
     *
     * @return $this
     * @throws Exception\RuntimeException
     * @template TNewKey of array-key
     * @template TNewValue
     * @param self<TNewKey, TNewValue> $textDomain
     * @psalm-self-out self<TKey|TNewKey, TValue|TNewValue>
     */
    public function merge(Text_Domain $text_domain): static
    {
        if ($this->has_plural_rule() && $text_domain->has_plural_rule()) {
            if ($this->get_plural_rule()->get_num_plurals() !== $text_domain->get_plural_rule()->get_num_plurals()) {
                throw new Exception\RuntimeException('Plural rule of merging text domain is not compatible with the current one');
            }
        } elseif ($text_domain->has_plural_rule()) {
            $this->set_plural_rule($text_domain->get_plural_rule());
        }
        $this->exchange_array(array_replace($this->get_array_copy(), $text_domain->get_array_copy()));
        return $this;
    }
}