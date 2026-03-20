<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Plural;

use function abs;
use function floor;
use Laminas\I18n\Exception;
use function preg_match;
use function sprintf;
/**
 * Plural rule evaluator.
 *
 * @final
 */
class Rule
{
    /**
     * Parser instance.
     *
     * @var Parser
     */
    protected static $parser;
    /**
     * Create a new plural rule.
     *
     * @param int   $numPlurals
     */
    protected function __construct(
        /**
         * Number of plurals in this rule.
         */
        protected $num_plurals,
        /**
         * Abstract syntax tree.
         */
        protected array $ast
    )
    {
    }
    /**
     * Evaluate a number and return the plural index.
     *
     * @param  int $number
     * @return int
     * @throws Exception\RangeException
     */
    public function evaluate($number)
    {
        $result = $this->evaluate_ast_part($this->ast, abs((int) $number));
        if ($result < 0 || $result >= $this->num_plurals) {
            throw new Exception\RangeException(sprintf('Calculated result %s is between 0 and %d', $result, $this->num_plurals - 1));
        }
        return $result;
    }
    /**
     * Get number of possible plural forms.
     *
     * @return int
     */
    public function get_num_plurals()
    {
        return $this->num_plurals;
    }
    /**
     * Evaluate a part of an ast.
     *
     * @param  int   $number
     * @return int
     * @throws Exception\ParseException
     */
    protected function evaluate_ast_part(array $ast, $number)
    {
        return match ($ast['id']) {
            'number' => $ast['arguments'][0],
            'n' => $number,
            '+' => $this->evaluate_ast_part($ast['arguments'][0], $number) + $this->evaluate_ast_part($ast['arguments'][1], $number),
            '-' => $this->evaluate_ast_part($ast['arguments'][0], $number) - $this->evaluate_ast_part($ast['arguments'][1], $number),
            // Integer division
            '/' => floor($this->evaluate_ast_part($ast['arguments'][0], $number) / $this->evaluate_ast_part($ast['arguments'][1], $number)),
            '*' => $this->evaluate_ast_part($ast['arguments'][0], $number) * $this->evaluate_ast_part($ast['arguments'][1], $number),
            '%' => $this->evaluate_ast_part($ast['arguments'][0], $number) % $this->evaluate_ast_part($ast['arguments'][1], $number),
            '>' => $this->evaluate_ast_part($ast['arguments'][0], $number) > $this->evaluate_ast_part($ast['arguments'][1], $number) ? 1 : 0,
            '>=' => $this->evaluate_ast_part($ast['arguments'][0], $number) >= $this->evaluate_ast_part($ast['arguments'][1], $number) ? 1 : 0,
            '<' => $this->evaluate_ast_part($ast['arguments'][0], $number) < $this->evaluate_ast_part($ast['arguments'][1], $number) ? 1 : 0,
            '<=' => $this->evaluate_ast_part($ast['arguments'][0], $number) <= $this->evaluate_ast_part($ast['arguments'][1], $number) ? 1 : 0,
            // @codingStandardsIgnoreStart
            '==' => $this->evaluate_ast_part($ast['arguments'][0], $number) == $this->evaluate_ast_part($ast['arguments'][1], $number) ? 1 : 0,
            '!=' => $this->evaluate_ast_part($ast['arguments'][0], $number) != $this->evaluate_ast_part($ast['arguments'][1], $number) ? 1 : 0,
            '&&' => $this->evaluate_ast_part($ast['arguments'][0], $number) && $this->evaluate_ast_part($ast['arguments'][1], $number) ? 1 : 0,
            '||' => $this->evaluate_ast_part($ast['arguments'][0], $number) || $this->evaluate_ast_part($ast['arguments'][1], $number) ? 1 : 0,
            '!' => !$this->evaluate_ast_part($ast['arguments'][0], $number) ? 1 : 0,
            '?' => $this->evaluate_ast_part($ast['arguments'][0], $number) ? $this->evaluate_ast_part($ast['arguments'][1], $number) : $this->evaluate_ast_part($ast['arguments'][2], $number),
            default => throw new Exception\Parse_Exception(sprintf('Unknown token: %s', $ast['id'])),
        };
    }
    /**
     * Create a new rule from a string.
     *
     * @param  string $string
     * @throws Exception\ParseException
     */
    public static function from_string($string): static
    {
        if (static::$parser === null) {
            static::$parser = new Parser();
        }
        if (!preg_match('(nplurals=(?P<nplurals>\d+))', $string, $match)) {
            throw new Exception\Parse_Exception(sprintf('Unknown or invalid parser rule: %s', $string));
        }
        $num_plurals = (int) $match['nplurals'];
        if (!preg_match('(plural=(?P<plural>[^;\n]+))', $string, $match)) {
            throw new Exception\Parse_Exception(sprintf('Unknown or invalid parser rule: %s', $string));
        }
        $tree = static::$parser->parse($match['plural']);
        $ast = static::create_ast($tree);
        return new static($num_plurals, $ast);
    }
    /**
     * Create an AST from a tree.
     *
     * Theoretically we could just use the given Symbol, but that one is not
     * so easy to serialize and also takes up more memory.
     */
    protected static function create_ast(Symbol $symbol): array
    {
        $ast = ['id' => $symbol->id, 'arguments' => []];
        switch ($symbol->id) {
            case 'n':
                break;
            case 'number':
                $ast['arguments'][] = $symbol->value;
                break;
            case '!':
                $ast['arguments'][] = static::create_ast($symbol->first);
                break;
            case '?':
                $ast['arguments'][] = static::create_ast($symbol->first);
                $ast['arguments'][] = static::create_ast($symbol->second);
                $ast['arguments'][] = static::create_ast($symbol->third);
                break;
            default:
                $ast['arguments'][] = static::create_ast($symbol->first);
                $ast['arguments'][] = static::create_ast($symbol->second);
                break;
        }
        return $ast;
    }
}