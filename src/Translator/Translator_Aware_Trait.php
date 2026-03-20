<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator;

trait Translator_Aware_Trait
{
    /** @var TranslatorInterface|null */
    protected $translator;
    /** @var bool */
    protected $translator_enabled = true;
    /** @var string */
    protected $translator_text_domain = 'default';
    /**
     * Sets translator to use in helper
     *
     * @param string|null              $textDomain
     * @return $this
     */
    public function set_translator(?Translator_Interface $translator = null, $text_domain = null)
    {
        $this->translator = $translator;
        if (null !== $text_domain) {
            $this->set_translator_text_domain($text_domain);
        }
        return $this;
    }
    /**
     * Returns translator used in object
     *
     * @return TranslatorInterface|null
     */
    public function get_translator()
    {
        return $this->translator;
    }
    /**
     * Checks if the object has a translator
     */
    public function has_translator(): bool
    {
        return null !== $this->translator;
    }
    /**
     * Sets whether translator is enabled and should be used
     *
     * @param bool $enabled
     * @return $this
     */
    public function set_translator_enabled($enabled = true)
    {
        $this->translator_enabled = $enabled;
        return $this;
    }
    /**
     * Returns whether translator is enabled and should be used
     *
     * @return bool
     */
    public function is_translator_enabled()
    {
        return $this->translator_enabled;
    }
    /**
     * Set translation text domain
     *
     * @param string $textDomain
     * @return $this
     */
    public function set_translator_text_domain($text_domain = 'default')
    {
        $this->translator_text_domain = $text_domain;
        return $this;
    }
    /**
     * Return the translation text domain
     *
     * @return string
     */
    public function get_translator_text_domain()
    {
        return $this->translator_text_domain;
    }
}