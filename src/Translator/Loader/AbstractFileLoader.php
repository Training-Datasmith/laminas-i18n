<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator\Loader;

use function is_file;
use function is_readable;
use function stream_resolve_include_path;
/**
 * Abstract file loader implementation; provides facilities around resolving
 * files via the include_path.
 */
abstract class Abstract_File_Loader implements File_Loader_Interface
{
    /**
     * Whether or not to consult the include_path when locating files
     *
     * @var bool
     */
    protected $use_include_path = false;
    /**
     * Indicate whether or not to use the include_path to resolve translation files
     *
     * @param bool $flag
     * @return self
     */
    public function set_use_include_path($flag = true)
    {
        $this->use_include_path = (bool) $flag;
        return $this;
    }
    /**
     * Are we using the include_path to resolve translation files?
     *
     * @return bool
     */
    public function use_include_path()
    {
        return $this->use_include_path;
    }
    /**
     * Resolve a translation file
     *
     * Checks if the file exists and is readable, returning a boolean false if not; if the "useIncludePath"
     * flag is enabled, it will attempt to resolve the file from the
     * include_path if the file does not exist on the current working path.
     *
     * @param string $filename
     * @return string|false
     */
    protected function resolve_file($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            if (!$this->use_include_path()) {
                return false;
            }
            return $this->resolve_via_include_path($filename);
        }
        return $filename;
    }
    /**
     * Resolve a translation file via the include_path
     *
     * @param string $filename
     * @return string|false
     */
    protected function resolve_via_include_path($filename)
    {
        $resolved_include_path = stream_resolve_include_path($filename);
        if ($resolved_include_path === false || !is_file($resolved_include_path) || !is_readable($resolved_include_path)) {
            return false;
        }
        return $resolved_include_path;
    }
}