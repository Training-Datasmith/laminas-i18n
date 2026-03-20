<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Plural;

use function ctype_digit;
use Laminas\I18n\Exception;
use function max;
use function sprintf;
/**
 * Plural rule parser.
 *
 * This plural rule parser is implemented after the article "Top Down Operator
 * Precedence" described in <http://javascript.crockford.com/tdop/tdop.html>.
 *
 * @final
 */
class Parser
{
    /**
     * String to parse.
     *
     * @var string
     */
    protected $string;
    /**
     * Current lexer position in the string.
     *
     * @var int
     */
    protected $current_pos;
    /**
     * Current token.
     *
     * @var Symbol
     */
    protected $current_token;
    /**
     * Table of symbols.
     *
     * @var Symbol[]
     */
    protected $symbol_table = [];
    /**
     * Create a new plural parser.
     */
    public function __construct()
    {
        $this->populate_symbol_table();
    }
    /**
     * Populate the symbol table.
     *
     * @return void
     */
    protected function populate_symbol_table()
    {
        // Ternary operators
        $this->register_symbol('?', 20)->set_left_denotation_getter(
            // @codingStandardsIgnoreStart Generic.WhiteSpace.ScopeIndent.IncorrectExact
            static function (Symbol $self, Symbol $left): \Laminas\I18n\Translator\Plural\Symbol {
                $self->first = $left;
                $self->second = $self->parser->expression();
                $self->parser->advance(':');
                $self->third = $self->parser->expression();
                return $self;
            }
        );
        $this->register_symbol(':');
        // Boolean operators
        $this->register_left_infix_symbol('||', 30);
        $this->register_left_infix_symbol('&&', 40);
        // Equal operators
        $this->register_left_infix_symbol('==', 50);
        $this->register_left_infix_symbol('!=', 50);
        // Compare operators
        $this->register_left_infix_symbol('>', 50);
        $this->register_left_infix_symbol('<', 50);
        $this->register_left_infix_symbol('>=', 50);
        $this->register_left_infix_symbol('<=', 50);
        // Add operators
        $this->register_left_infix_symbol('-', 60);
        $this->register_left_infix_symbol('+', 60);
        // Multiply operators
        $this->register_left_infix_symbol('*', 70);
        $this->register_left_infix_symbol('/', 70);
        $this->register_left_infix_symbol('%', 70);
        // Not operator
        $this->register_prefix_symbol('!', 80);
        // Literals
        $this->register_symbol('n')->set_null_denotation_getter(
            // @codingStandardsIgnoreStart Generic.WhiteSpace.ScopeIndent.IncorrectExact
            static fn(Symbol $self): \Laminas\I18n\Translator\Plural\Symbol => $self
        );
        $this->register_symbol('number')->set_null_denotation_getter(
            // @codingStandardsIgnoreStart Generic.WhiteSpace.ScopeIndent.IncorrectExact
            static fn(Symbol $self): \Laminas\I18n\Translator\Plural\Symbol => $self
        );
        // Parentheses
        $this->register_symbol('(')->set_null_denotation_getter(
            // @codingStandardsIgnoreStart Generic.WhiteSpace.ScopeIndent.IncorrectExact
            static function (Symbol $self) {
                $expression = $self->parser->expression();
                $self->parser->advance(')');
                return $expression;
            }
        );
        $this->register_symbol(')');
        // Eof
        $this->register_symbol('eof');
    }
    /**
     * Register a left infix symbol.
     *
     * @param  string  $id
     * @param  int $leftBindingPower
     * @return void
     */
    protected function register_left_infix_symbol($id, $left_binding_power)
    {
        $this->register_symbol($id, $left_binding_power)->set_left_denotation_getter(
            // @codingStandardsIgnoreStart Generic.WhiteSpace.ScopeIndent.IncorrectExact
            static function (Symbol $self, Symbol $left) use ($left_binding_power): \Laminas\I18n\Translator\Plural\Symbol {
                $self->first = $left;
                $self->second = $self->parser->expression($left_binding_power);
                return $self;
            }
        );
    }
    /**
     * Register a right infix symbol.
     *
     * @param  string  $id
     * @param  int $leftBindingPower
     * @return void
     */
    protected function register_right_infix_symbol($id, $left_binding_power)
    {
        $this->register_symbol($id, $left_binding_power)->set_left_denotation_getter(
            // @codingStandardsIgnoreStart Generic.WhiteSpace.ScopeIndent.IncorrectExact
            static function (Symbol $self, Symbol $left) use ($left_binding_power): \Laminas\I18n\Translator\Plural\Symbol {
                $self->first = $left;
                $self->second = $self->parser->expression($left_binding_power - 1);
                return $self;
            }
        );
    }
    /**
     * Register a prefix symbol.
     *
     * @param  string  $id
     * @param  int $leftBindingPower
     * @return void
     */
    protected function register_prefix_symbol($id, $left_binding_power)
    {
        $this->register_symbol($id, $left_binding_power)->set_null_denotation_getter(
            // @codingStandardsIgnoreStart Generic.WhiteSpace.ScopeIndent.IncorrectExact
            static function (Symbol $self) use ($left_binding_power): \Laminas\I18n\Translator\Plural\Symbol {
                $self->first = $self->parser->expression($left_binding_power);
                $self->second = null;
                return $self;
            }
        );
    }
    /**
     * Register a symbol.
     *
     * @param  string  $id
     * @param  int $leftBindingPower
     * @return Symbol
     */
    protected function register_symbol($id, $left_binding_power = 0)
    {
        if (isset($this->symbol_table[$id])) {
            $symbol = $this->symbol_table[$id];
            $symbol->left_binding_power = max($symbol->left_binding_power, $left_binding_power);
        } else {
            $symbol = new Symbol($this, $id, $left_binding_power);
            $this->symbol_table[$id] = $symbol;
        }
        return $symbol;
    }
    /**
     * Get a new symbol.
     *
     * @param string $id
     * @return Symbol
     */
    protected function get_symbol($id)
    {
        if (!isset($this->symbol_table[$id])) {
            // phpcs:ignore
            // Unknown symbol exception
        }
        return clone $this->symbol_table[$id];
    }
    /**
     * Parse a string.
     *
     * @return Symbol
     */
    public function parse(string $string)
    {
        $this->string = $string . "\x00";
        $this->current_pos = 0;
        $this->current_token = $this->get_next_token();
        return $this->expression();
    }
    /**
     * Parse an expression.
     *
     * @param  int $rightBindingPower
     * @return Symbol
     */
    public function expression($right_binding_power = 0)
    {
        $token = $this->current_token;
        $this->current_token = $this->get_next_token();
        $left = $token->get_null_denotation();
        while ($right_binding_power < $this->current_token->left_binding_power) {
            $token = $this->current_token;
            $this->current_token = $this->get_next_token();
            $left = $token->get_left_denotation($left);
        }
        return $left;
    }
    /**
     * Advance the current token and optionally check the old token id.
     *
     * @param  string $id
     * @throws Exception\ParseException
     */
    public function advance($id = null): void
    {
        if ($id !== null && $this->current_token->id !== $id) {
            throw new Exception\Parse_Exception(sprintf('Expected token with id %s but received %s', $id, $this->current_token->id));
        }
        $this->current_token = $this->get_next_token();
    }
    /**
     * Get the next token.
     *
     * @return Symbol
     * @throws Exception\ParseException
     */
    protected function get_next_token()
    {
        while ($this->string[$this->current_pos] === ' ' || $this->string[$this->current_pos] === "\t") {
            $this->current_pos++;
        }
        $result = $this->string[$this->current_pos++];
        $value = null;
        switch ($result) {
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                while (ctype_digit($this->string[$this->current_pos])) {
                    $result .= $this->string[$this->current_pos++];
                }
                $id = 'number';
                $value = (int) $result;
                break;
            case '=':
            case '&':
            case '|':
                if ($this->string[$this->current_pos] === $result) {
                    $this->current_pos++;
                    $id = $result . $result;
                } else {
                    // phpcs:ignore
                    // Yield error
                }
                break;
            case '!':
            case '<':
            case '>':
                if ($this->string[$this->current_pos] === '=') {
                    $this->current_pos++;
                    $result .= '=';
                }
                $id = $result;
                break;
            case '*':
            case '/':
            case '%':
            case '+':
            case '-':
            case 'n':
            case '?':
            case ':':
            case '(':
            case ')':
                $id = $result;
                break;
            case ';':
            case "\n":
            case "\x00":
                $id = 'eof';
                $this->current_pos--;
                break;
            default:
                throw new Exception\Parse_Exception(sprintf('Found invalid character "%s" in input stream', $result));
        }
        $token = $this->get_symbol($id);
        $token->value = $value;
        return $token;
    }
}