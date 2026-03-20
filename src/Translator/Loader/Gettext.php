<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Loader;

use function array_shift;
use function explode;
use function fclose;
use function fopen;
use function fread;
use function fseek;
use Laminas\I18n\Exception;
use Laminas\I18n\Translator\Plural\Rule as PluralRule;
use Laminas\I18n\Translator\Text_Domain;
use Laminas\Stdlib\Error_Handler;
use function sprintf;
use function strtolower;
use function trim;
use function unpack;
/**
 * Gettext loader.
 *
 * @final
 */
class Gettext extends Abstract_File_Loader
{
    /**
     * Current file pointer.
     *
     * @var resource
     */
    protected $file;
    /**
     * Whether the current file is little endian.
     *
     * @var bool
     */
    protected $little_endian;
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
        $resolved_file = $this->resolve_file($filename);
        if ($resolved_file === false) {
            throw new Exception\InvalidArgumentException(sprintf('Could not find or open file %s for reading', $filename));
        }
        $text_domain = new Text_Domain();
        Error_Handler::start();
        $this->file = fopen($resolved_file, 'rb');
        $error = Error_Handler::stop();
        if (false === $this->file) {
            throw new Exception\InvalidArgumentException(sprintf('Could not open file %s for reading', $filename), 0, $error);
        }
        // Verify magic number
        $magic = fread($this->file, 4);
        if ($magic === "\x95\x04\x12\xde") {
            $this->little_endian = false;
        } elseif ($magic === "\xde\x12\x04\x95") {
            $this->little_endian = true;
        } else {
            fclose($this->file);
            throw new Exception\InvalidArgumentException(sprintf('%s is not a valid gettext file', $filename));
        }
        // Verify major revision (only 0 and 1 supported)
        $major_revision = $this->read_integer() >> 16;
        if ($major_revision !== 0 && $major_revision !== 1) {
            fclose($this->file);
            throw new Exception\InvalidArgumentException(sprintf('%s has an unknown major revision', $filename));
        }
        // Gather main information
        $num_strings = $this->read_integer();
        $original_string_table_offset = $this->read_integer();
        $translation_string_table_offset = $this->read_integer();
        // Usually there follow size and offset of the hash table, but we have
        // no need for it, so we skip them.
        fseek($this->file, $original_string_table_offset);
        $original_string_table = $this->read_integer_list(2 * $num_strings);
        fseek($this->file, $translation_string_table_offset);
        $translation_string_table = $this->read_integer_list(2 * $num_strings);
        // Read in all translations
        for ($current = 0; $current < $num_strings; $current++) {
            $size_key = $current * 2 + 1;
            $offset_key = $current * 2 + 2;
            $original_string_size = $original_string_table[$size_key];
            $original_string_offset = $original_string_table[$offset_key];
            $translation_string_size = $translation_string_table[$size_key];
            $translation_string_offset = $translation_string_table[$offset_key];
            $original_string = [''];
            if ($original_string_size > 0) {
                fseek($this->file, $original_string_offset);
                $original_string = explode("\x00", fread($this->file, $original_string_size));
            }
            if ($translation_string_size > 0) {
                fseek($this->file, $translation_string_offset);
                $translation_string = explode("\x00", fread($this->file, $translation_string_size));
                if (isset($original_string[1], $translation_string[1])) {
                    $text_domain[$original_string[0]] = $translation_string;
                    array_shift($original_string);
                    foreach ($original_string as $string) {
                        if (!isset($text_domain[$string])) {
                            $text_domain[$string] = '';
                        }
                    }
                } else {
                    $text_domain[$original_string[0]] = $translation_string[0];
                }
            }
        }
        // Read header entries
        if ($text_domain->offsetExists('')) {
            $raw_headers = explode("\n", trim((string) $text_domain['']));
            foreach ($raw_headers as $raw_header) {
                [$header, $content] = explode(':', $raw_header, 2);
                if (strtolower(trim($header)) === 'plural-forms') {
                    $text_domain->set_plural_rule(Plural_Rule::from_string($content));
                }
            }
            unset($text_domain['']);
        }
        fclose($this->file);
        return $text_domain;
    }
    /**
     * Read a single integer from the current file.
     *
     * @return int
     */
    protected function read_integer()
    {
        if ($this->little_endian) {
            $result = unpack('Vint', fread($this->file, 4));
        } else {
            $result = unpack('Nint', fread($this->file, 4));
        }
        return $result['int'];
    }
    /**
     * Read an integer from the current file.
     *
     * @param  int $num
     * @return int
     */
    protected function read_integer_list($num): array|false
    {
        if ($this->little_endian) {
            return unpack('V' . $num, fread($this->file, 4 * $num));
        }
        return unpack('N' . $num, fread($this->file, 4 * $num));
    }
}