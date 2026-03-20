<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator;

use function array_shift;
use function get_debug_type;
use function is_array;
use function is_file;
use function is_string;
use Laminas\Cache;
use Laminas\Cache\Storage\Storage_Interface as CacheStorage;
use Laminas\Event_Manager\Event;
use Laminas\Event_Manager\Event_Manager;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\I18n\Exception;
use Laminas\I18n\Translator\Loader\File_Loader_Interface;
use Laminas\I18n\Translator\Loader\Remote_Loader_Interface;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Stdlib\Array_Utils;
use Locale;
use function md5;
use function rtrim;
use function sprintf;
use Traversable;
/**
 * Translator.
 *
 * @final
 */
class Translator implements Translator_Interface
{
    /**
     * Event fired when the translation for a message is missing.
     */
    public const EVENT_MISSING_TRANSLATION = 'missingTranslation';
    /**
     * Event fired when no messages were loaded for a locale/text-domain combination.
     */
    public const EVENT_NO_MESSAGES_LOADED = 'noMessagesLoaded';
    /**
     * Messages loaded by the translator.
     *
     * @var array
     */
    protected $messages = [];
    /**
     * Files used for loading messages.
     *
     * @var array
     */
    protected $files = [];
    /**
     * Patterns used for loading messages.
     *
     * @var array
     */
    protected $patterns = [];
    /**
     * Remote locations for loading messages.
     *
     * @var array
     */
    protected $remote = [];
    /**
     * Default locale.
     *
     * @var string|null
     */
    protected $locale;
    /**
     * Locale to use as fallback if there is no translation.
     *
     * @var string|null
     */
    protected $fallback_locale;
    /**
     * Translation cache.
     *
     * @var CacheStorage|null
     */
    protected $cache;
    /**
     * Plugin manager for translation loaders.
     *
     * @var LoaderPluginManager
     */
    protected $plugin_manager;
    /**
     * Event manager for triggering translator events.
     *
     * @var EventManagerInterface
     */
    protected $events;
    /**
     * Whether events are enabled
     *
     * @var bool
     */
    protected $events_enabled = false;
    /**
     * Instantiate a translator
     *
     * @param  array|Traversable $options
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($options): static
    {
        if ($options instanceof Traversable) {
            $options = Array_Utils::iterator_to_array($options);
        } elseif (!is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf('%s expects an array or Traversable object; received "%s"', __METHOD__, get_debug_type($options)));
        }
        $translator = new static();
        // locales
        if (isset($options['locale'])) {
            $locales = (array) $options['locale'];
            $translator->set_locale(array_shift($locales));
            if ($locales) {
                $translator->set_fallback_locale(array_shift($locales));
            }
        }
        // file patterns
        if (isset($options['translation_file_patterns'])) {
            if (!is_array($options['translation_file_patterns'])) {
                throw new Exception\InvalidArgumentException('"translation_file_patterns" should be an array');
            }
            $required_keys = ['type', 'base_dir', 'pattern'];
            foreach ($options['translation_file_patterns'] as $pattern) {
                foreach ($required_keys as $key) {
                    if (!isset($pattern[$key])) {
                        throw new Exception\InvalidArgumentException("'{$key}' is missing for translation pattern options");
                    }
                }
                $translator->add_translation_file_pattern($pattern['type'], $pattern['base_dir'], $pattern['pattern'], $pattern['text_domain'] ?? 'default');
            }
        }
        // files
        if (isset($options['translation_files'])) {
            if (!is_array($options['translation_files'])) {
                throw new Exception\InvalidArgumentException('"translation_files" should be an array');
            }
            $required_keys = ['type', 'filename'];
            foreach ($options['translation_files'] as $file) {
                foreach ($required_keys as $key) {
                    if (!isset($file[$key])) {
                        throw new Exception\InvalidArgumentException("'{$key}' is missing for translation file options");
                    }
                }
                $translator->add_translation_file($file['type'], $file['filename'], $file['text_domain'] ?? 'default', $file['locale'] ?? null);
            }
        }
        // remote
        if (isset($options['remote_translation'])) {
            if (!is_array($options['remote_translation'])) {
                throw new Exception\InvalidArgumentException('"remote_translation" should be an array');
            }
            $required_keys = ['type'];
            foreach ($options['remote_translation'] as $remote) {
                foreach ($required_keys as $key) {
                    if (!isset($remote[$key])) {
                        throw new Exception\InvalidArgumentException("'{$key}' is missing for remote translation options");
                    }
                }
                $translator->add_remote_translations($remote['type'], $remote['text_domain'] ?? 'default');
            }
        }
        // cache
        if (isset($options['cache'])) {
            if ($options['cache'] instanceof Cache_Storage) {
                $translator->set_cache($options['cache']);
            } else {
                $translator->set_cache(Cache\Storage_Factory::factory($options['cache']));
            }
        }
        // event manager enabled
        if (isset($options['event_manager_enabled']) && $options['event_manager_enabled']) {
            $translator->enable_event_manager();
        }
        return $translator;
    }
    /**
     * Set the default locale.
     *
     * @param  string|null $locale
     * @return $this
     */
    public function set_locale($locale): static
    {
        $this->locale = $locale;
        return $this;
    }
    /**
     * Get the default locale.
     *
     * @return string
     */
    public function get_locale()
    {
        if ($this->locale === null) {
            $this->locale = Locale::get_default();
        }
        return $this->locale;
    }
    /**
     * Set the fallback locale.
     *
     * @param  string|null $locale
     * @return $this
     */
    public function set_fallback_locale($locale): static
    {
        $this->fallback_locale = $locale;
        return $this;
    }
    /**
     * Get the fallback locale.
     *
     * @return string|null
     */
    public function get_fallback_locale()
    {
        return $this->fallback_locale;
    }
    /**
     * Sets a cache
     *
     * @return $this
     */
    public function set_cache(?Cache_Storage $cache = null): static
    {
        $this->cache = $cache;
        return $this;
    }
    /**
     * Returns the set cache
     *
     * @return CacheStorage|null The set cache
     */
    public function get_cache()
    {
        return $this->cache;
    }
    /**
     * Set the plugin manager for translation loaders
     *
     * @return $this
     */
    public function set_plugin_manager(Loader_Plugin_Manager $plugin_manager): static
    {
        $this->plugin_manager = $plugin_manager;
        return $this;
    }
    /**
     * Retrieve the plugin manager for translation loaders.
     *
     * Lazy loads an instance if none currently set.
     *
     * @return LoaderPluginManager
     */
    public function get_plugin_manager()
    {
        if (!$this->plugin_manager instanceof Loader_Plugin_Manager) {
            $this->set_plugin_manager(new Loader_Plugin_Manager(new Service_Manager()));
        }
        return $this->plugin_manager;
    }
    /**
     * Translate a message.
     *
     * @param  string      $message
     * @param  string      $textDomain
     * @param  string|null $locale
     * @return string
     */
    public function translate($message, $text_domain = 'default', $locale = null)
    {
        $locale = $locale === '' ? null : $locale;
        $locale ??= $this->get_locale();
        $translation = $this->get_translated_message($message, $locale, $text_domain);
        if ($translation !== null && $translation !== '') {
            return $translation;
        }
        if (null !== ($fallback_locale = $this->get_fallback_locale()) && $locale !== $fallback_locale) {
            return $this->translate($message, $text_domain, $fallback_locale);
        }
        return $message;
    }
    /**
     * Translate a plural message.
     *
     * @param  string      $singular
     * @param  string      $plural
     * @param  int         $number
     * @param  string      $textDomain
     * @param  string|null $locale
     * @return string
     * @throws Exception\OutOfBoundsException
     */
    public function translate_plural($singular, $plural, $number, $text_domain = 'default', $locale = null)
    {
        $locale ??= $this->get_locale();
        $translation = $this->get_translated_message($singular, $locale, $text_domain);
        if (is_string($translation)) {
            $translation = [$translation];
        }
        $index = $number === 1 ? 0 : 1;
        // en_EN Plural rule
        if ($this->messages[$text_domain][$locale] instanceof Text_Domain) {
            $index = $this->messages[$text_domain][$locale]->get_plural_rule()->evaluate($number);
        }
        if (isset($translation[$index]) && $translation[$index] !== '' && $translation[$index] !== null) {
            return $translation[$index];
        }
        if (null !== ($fallback_locale = $this->get_fallback_locale()) && $locale !== $fallback_locale) {
            return $this->translate_plural($singular, $plural, $number, $text_domain, $fallback_locale);
        }
        return $index === 0 ? $singular : $plural;
    }
    /**
     * Get a translated message.
     *
     * @triggers getTranslatedMessage.missing-translation
     * @param    string $message
     * @param    string $locale
     * @param    string $textDomain
     * @return   string|null
     */
    protected function get_translated_message($message, $locale, $text_domain = 'default')
    {
        if ($message === '' || $message === null) {
            return '';
        }
        if (!isset($this->messages[$text_domain][$locale])) {
            $this->load_messages($text_domain, $locale);
        }
        if (isset($this->messages[$text_domain][$locale][$message])) {
            return $this->messages[$text_domain][$locale][$message];
        }
        /**
         * issue https://github.com/zendframework/zend-i18n/issues/53
         *
         * storage: array:8 [▼
         *   "default\x04Welcome" => "Cześć"
         *   "default\x04Top %s Product" => array:3 [▼
         *     0 => "Top %s Produkt"
         *     1 => "Top %s Produkty"
         *     2 => "Top %s Produktów"
         *   ]
         *   "Top %s Products" => ""
         * ]
         */
        if (isset($this->messages[$text_domain][$locale][$text_domain . "\x04" . $message])) {
            return $this->messages[$text_domain][$locale][$text_domain . "\x04" . $message];
        }
        if ($this->is_event_manager_enabled()) {
            $until = static fn($r): bool => is_string($r);
            $event = new Event(self::EVENT_MISSING_TRANSLATION, $this, ['message' => $message, 'locale' => $locale, 'text_domain' => $text_domain]);
            $results = $this->get_event_manager()->trigger_event_until($until, $event);
            $last = $results->last();
            if (is_string($last)) {
                return $last;
            }
        }
        return null;
    }
    /**
     * Add a translation file.
     *
     * @param  string      $type
     * @param  string      $filename
     * @param  string      $textDomain
     * @param  string|null $locale
     * @return $this
     */
    public function add_translation_file($type, $filename, $text_domain = 'default', $locale = null): static
    {
        $locale ??= '*';
        if (!isset($this->files[$text_domain])) {
            $this->files[$text_domain] = [];
        }
        $this->files[$text_domain][$locale][] = ['type' => $type, 'filename' => $filename];
        return $this;
    }
    /**
     * Add multiple translations with a file pattern.
     *
     * @param  string $type
     * @param  string $baseDir
     * @param  string $pattern
     * @param  string $textDomain
     * @return $this
     */
    public function add_translation_file_pattern($type, $base_dir, $pattern, $text_domain = 'default'): static
    {
        if (!isset($this->patterns[$text_domain])) {
            $this->patterns[$text_domain] = [];
        }
        $this->patterns[$text_domain][] = ['type' => $type, 'baseDir' => rtrim($base_dir, '/'), 'pattern' => $pattern];
        return $this;
    }
    /**
     * Add remote translations.
     *
     * @param  string $type
     * @param  string $textDomain
     * @return $this
     */
    public function add_remote_translations($type, $text_domain = 'default'): static
    {
        if (!isset($this->remote[$text_domain])) {
            $this->remote[$text_domain] = [];
        }
        $this->remote[$text_domain][] = $type;
        return $this;
    }
    /**
     * Get the cache identifier for a specific textDomain and locale.
     */
    public function get_cache_id(string $text_domain, string $locale): string
    {
        return 'Laminas_I18n_Translator_Messages_' . md5($text_domain . $locale);
    }
    /**
     * Clears the cache for a specific textDomain and locale.
     *
     * @param  string $textDomain
     * @param  string $locale
     * @return bool
     */
    public function clear_cache($text_domain, $locale)
    {
        if (null === $cache = $this->get_cache()) {
            return false;
        }
        return $cache->remove_item($this->get_cache_id($text_domain, $locale));
    }
    /**
     * Load messages for a given language and domain.
     *
     * @triggers loadMessages.no-messages-loaded
     * @param    string $textDomain
     * @param    string $locale
     * @throws   Exception\RuntimeException
     * @return   void
     */
    protected function load_messages($text_domain, $locale)
    {
        if (!isset($this->messages[$text_domain])) {
            $this->messages[$text_domain] = [];
        }
        if (null !== $cache = $this->get_cache()) {
            $cache_id = $this->get_cache_id($text_domain, $locale);
            if (null !== $result = $cache->get_item($cache_id)) {
                $this->messages[$text_domain][$locale] = $result;
                return;
            }
        }
        $messages_loaded = 0;
        $messages_loaded |= (int) $this->load_messages_from_remote($text_domain, $locale);
        $messages_loaded |= (int) $this->load_messages_from_patterns($text_domain, $locale);
        $messages_loaded |= (int) $this->load_messages_from_files($text_domain, $locale);
        if ($messages_loaded === 0) {
            $discovered_text_domain = null;
            if ($this->is_event_manager_enabled()) {
                $until = static fn($r): bool => $r instanceof Text_Domain;
                $event = new Event(self::EVENT_NO_MESSAGES_LOADED, $this, ['locale' => $locale, 'text_domain' => $text_domain]);
                $results = $this->get_event_manager()->trigger_event_until($until, $event);
                $last = $results->last();
                if ($last instanceof Text_Domain) {
                    $discovered_text_domain = $last;
                }
            }
            $this->messages[$text_domain][$locale] = $discovered_text_domain;
        }
        if ($cache !== null) {
            $cache->set_item($cache_id, $this->messages[$text_domain][$locale]);
        }
    }
    /**
     * Load messages from remote sources.
     *
     * @param  string $textDomain
     * @param  string $locale
     * @return bool
     * @throws Exception\RuntimeException When specified loader is not a remote loader.
     */
    protected function load_messages_from_remote($text_domain, $locale)
    {
        $messages_loaded = false;
        if (isset($this->remote[$text_domain])) {
            foreach ($this->remote[$text_domain] as $loader_type) {
                $loader = $this->get_plugin_manager()->get($loader_type);
                if (!$loader instanceof Remote_Loader_Interface) {
                    throw new Exception\RuntimeException('Specified loader is not a remote loader');
                }
                if (isset($this->messages[$text_domain][$locale])) {
                    $this->messages[$text_domain][$locale]->merge($loader->load($locale, $text_domain));
                } else {
                    $this->messages[$text_domain][$locale] = $loader->load($locale, $text_domain);
                }
                $messages_loaded = true;
            }
        }
        return $messages_loaded;
    }
    /**
     * Load messages from patterns.
     *
     * @param  string $textDomain
     * @param  string $locale
     * @return bool
     * @throws Exception\RuntimeException When specified loader is not a file loader.
     */
    protected function load_messages_from_patterns($text_domain, $locale)
    {
        $messages_loaded = false;
        if (isset($this->patterns[$text_domain])) {
            foreach ($this->patterns[$text_domain] as $pattern) {
                $filename = $pattern['baseDir'] . '/' . sprintf($pattern['pattern'], $locale);
                if (is_file($filename)) {
                    $loader = $this->get_plugin_manager()->get($pattern['type']);
                    if (!$loader instanceof File_Loader_Interface) {
                        throw new Exception\RuntimeException('Specified loader is not a file loader');
                    }
                    if (isset($this->messages[$text_domain][$locale])) {
                        $this->messages[$text_domain][$locale]->merge($loader->load($locale, $filename));
                    } else {
                        $this->messages[$text_domain][$locale] = $loader->load($locale, $filename);
                    }
                    $messages_loaded = true;
                }
            }
        }
        return $messages_loaded;
    }
    /**
     * Load messages from files.
     *
     * @param  string $textDomain
     * @param  string $locale
     * @return bool
     * @throws Exception\RuntimeException When specified loader is not a file loader.
     */
    protected function load_messages_from_files($text_domain, $locale)
    {
        $messages_loaded = false;
        foreach ([$locale, '*'] as $current_locale) {
            if (!isset($this->files[$text_domain][$current_locale])) {
                continue;
            }
            foreach ($this->files[$text_domain][$current_locale] as $file) {
                $loader = $this->get_plugin_manager()->get($file['type']);
                if (!$loader instanceof File_Loader_Interface) {
                    throw new Exception\RuntimeException('Specified loader is not a file loader');
                }
                if (isset($this->messages[$text_domain][$locale])) {
                    $this->messages[$text_domain][$locale]->merge($loader->load($locale, $file['filename']));
                } else {
                    $this->messages[$text_domain][$locale] = $loader->load($locale, $file['filename']);
                }
                $messages_loaded = true;
            }
            unset($this->files[$text_domain][$current_locale]);
        }
        return $messages_loaded;
    }
    /**
     * Return all the messages.
     *
     * @param string      $textDomain
     * @param string|null $locale
     * @return mixed
     */
    public function get_all_messages($text_domain = 'default', $locale = null)
    {
        $locale ??= $this->get_locale();
        if (!isset($this->messages[$text_domain][$locale])) {
            $this->load_messages($text_domain, $locale);
        }
        return $this->messages[$text_domain][$locale];
    }
    /**
     * Get the event manager.
     *
     * @return EventManagerInterface
     */
    public function get_event_manager()
    {
        if (!$this->events instanceof Event_Manager_Interface) {
            $this->set_event_manager(new Event_Manager());
        }
        return $this->events;
    }
    /**
     * Set the event manager instance used by this translator.
     *
     * @return $this
     */
    public function set_event_manager(Event_Manager_Interface $events): static
    {
        $events->set_identifiers([self::class, static::class, 'translator']);
        $this->events = $events;
        return $this;
    }
    /**
     * Check whether the event manager is enabled.
     *
     * @return bool
     */
    public function is_event_manager_enabled()
    {
        return $this->events_enabled;
    }
    /**
     * Enable the event manager.
     *
     * @return $this
     */
    public function enable_event_manager(): static
    {
        $this->events_enabled = true;
        return $this;
    }
    /**
     * Disable the event manager.
     *
     * @return $this
     */
    public function disable_event_manager(): static
    {
        $this->events_enabled = false;
        return $this;
    }
}