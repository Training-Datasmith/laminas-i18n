<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Plural;

use Closure;
use Laminas\I18n\Exception;
use function sprintf;
/**
 * Parser symbol.
 *
 * All properties in the symbol are defined as public for easier and faster
 * access from the applied closures. An exception are the closure properties
 * themselves, as they have to be accessed via the appropriate getter and
 * setter methods.
 *
 * @final
 */
class Symbol
{
    /**
     * Parser instance.
     *
     * @var Parser
     */
    public $parser;
    /**
     * Getter for null denotation.
     *
     * @var callable
     */
    protected $null_denotation_getter;
    /**
     * Getter for left denotation.
     *
     * @var callable
     */
    protected $left_denotation_getter;
    /**
     * Value used by literals.
     *
     * @var mixed
     */
    public $value;
    /**
     * First node value.
     *
     * @var Symbol
     */
    public $first;
    /**
     * Second node value.
     *
     * @var Symbol
     */
    public $second;
    /**
     * Third node value.
     *
     * @var Symbol
     */
    public $third;
    /**
     * Create a new symbol.
     *
     * @param  string  $id
     * @param  int $leftBindingPower
     */
    public function __construct(
        Parser $parser,
        /**
         * Node or token type name.
         */
        public $id,
        /**
         * Left binding power (precedence).
         */
        public $left_binding_power
    )
    {
        $this->parser = $parser;
    }
    /**
     * Set the null denotation getter.
     *
     * @return $this
     */
    public function set_null_denotation_getter(Closure $getter): static
    {
        $this->null_denotation_getter = $getter;
        return $this;
    }
    /**
     * Set the left denotation getter.
     *
     * @return $this
     */
    public function set_left_denotation_getter(Closure $getter): static
    {
        $this->left_denotation_getter = $getter;
        return $this;
    }
    /**
     * Get null denotation.
     *
     * @throws Exception\ParseException
     * @return Symbol
     */
    public function get_null_denotation()
    {
        if ($this->null_denotation_getter === null) {
            throw new Exception\Parse_Exception(sprintf('Syntax error: %s', $this->id));
        }
        /** @var callable $function  */
        $function = $this->null_denotation_getter;
        return $function($this);
    }
    /**
     * Get left denotation.
     *
     * @param  Symbol $left
     * @throws Exception\ParseException
     * @return Symbol
     */
    public function get_left_denotation($left)
    {
        if ($this->left_denotation_getter === null) {
            throw new Exception\Parse_Exception(sprintf('Unknown operator: %s', $this->id));
        }
        /** @var callable $function  */
        $function = $this->left_denotation_getter;
        return $function($this, $left);
    }
}