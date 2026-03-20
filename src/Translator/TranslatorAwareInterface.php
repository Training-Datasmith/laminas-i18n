<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator;

interface Translator_Aware_Interface
{
    /**
     * Sets translator to use in helper
     *
     * @param  TranslatorInterface|null $translator Default is null, which sets no translator.
     * @param  string|null              $textDomain Default is null, which skips setTranslatorTextDomain
     * @return $this
     */
    public function set_translator(?Translator_Interface $translator = null, $text_domain = null);
    /**
     * Returns translator used in object
     *
     * @return TranslatorInterface|null
     */
    public function get_translator();
    /**
     * Checks if the object has a translator
     *
     * @return bool
     */
    public function has_translator();
    /**
     * Sets whether translator is enabled and should be used
     *
     * @param  bool $enabled [optional] whether translator should be used.
     *                       Default is true.
     * @return $this
     */
    public function set_translator_enabled($enabled = true);
    /**
     * Returns whether translator is enabled and should be used
     *
     * @return bool
     */
    public function is_translator_enabled();
    /**
     * Set translation text domain
     *
     * @param  string $textDomain
     * @return $this
     */
    public function set_translator_text_domain($text_domain = 'default');
    /**
     * Return the translation text domain
     *
     * @return string
     */
    public function get_translator_text_domain();
}