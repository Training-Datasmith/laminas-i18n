<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator;

use function is_string;

use Laminas\I18n\Filter\Alpha as AlphaFilter;

/** @final */
class Alpha extends Alnum
{
    public const INVALID      = 'alphaInvalid';
    public const NOT_ALPHA    = 'notAlpha';
    public const STRING_EMPTY = 'alphaStringEmpty';

    /**
     * Alphabetic filter used for validation
     *
     * @var AlphaFilter|null
     */
    protected static $filter;

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::INVALID      => 'Invalid type given. String expected',
        self::NOT_ALPHA    => 'The input contains non alphabetic characters',
        self::STRING_EMPTY => 'The input is an empty string',
    ];

    /**
     * Options for this validator
     *
     * @var array<string, mixed>
     */
    protected $options = [
        'allowWhiteSpace' => false, // Whether to allow white space characters; off by default
    ];

    /**
     * Returns true if and only if $value contains only alphabetic characters
     *
     * @param mixed $value
     */
    public function isValid($value): bool
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        if ('' === $value) {
            $this->error(self::STRING_EMPTY);
            return false;
        }

        if (null === static::$filter) {
            static::$filter = new AlphaFilter();
        }

        static::$filter->setAllowWhiteSpace($this->getAllowWhiteSpace());

        if ($value !== static::$filter->filter($value)) {
            $this->error(self::NOT_ALPHA);
            return false;
        }

        return true;
    }
}
