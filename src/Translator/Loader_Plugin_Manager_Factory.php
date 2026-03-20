<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator;

use function is_array;
use Laminas\Service_Manager\Factory_Interface;
use Laminas\Service_Manager\Service_Locator_Interface;
use Laminas\Service_Manager\Service_Manager;
use Psr\Container\Container_Interface;
/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @final
 */
class Loader_Plugin_Manager_Factory implements Factory_Interface
{
    /**
     * laminas-servicemanager v2 options passed to factory.
     *
     * @deprecated Since 2.16.0 - This component is no longer compatible with Service Manager v2.
     *             This property will be removed in version 3.0
     *
     * @var array
     */
    protected $creation_options = [];
    /**
     * Create and return a LoaderPluginManager.
     *
     * @param string $name
     * @param array<string, mixed>|null $options
     * @psalm-param ServiceManagerConfiguration|null $options
     */
    public function __invoke(Container_Interface $container, $name, ?array $options = null): \Laminas\I18n\Translator\Loader_Plugin_Manager
    {
        $options ??= [];
        $plugin_manager = new Loader_Plugin_Manager($container, $options);
        // If this is in a laminas-mvc application, the ServiceListener will inject
        // merged configuration during bootstrap.
        if ($container->has('ServiceListener')) {
            return $plugin_manager;
        }
        // If we do not have a config service, nothing more to do
        if (!$container->has('config')) {
            return $plugin_manager;
        }
        $config = $container->get('config');
        // If we do not have translator_plugins configuration, nothing more to do
        if (!isset($config['translator_plugins']) || !is_array($config['translator_plugins'])) {
            return $plugin_manager;
        }
        // Wire service configuration for translator_plugins
        $plugin_manager->configure($config['translator_plugins']);
        return $plugin_manager;
    }
    /**
     * laminas-servicemanager v2 factory to return LoaderPluginManager
     *
     * @deprecated Since 2.16.0 - This component is no longer compatible with Service Manager v2.
     *             This method will be removed in version 3.0
     *
     * @return LoaderPluginManager
     */
    public function create_service(Service_Locator_Interface $container)
    {
        return $this($container, 'TranslatorPluginManager', $this->creation_options);
    }
    /**
     * v2 support for instance creation options.
     *
     * @deprecated Since 2.16.0 - This component is no longer compatible with Service Manager v2.
     *             This method will be removed in version 3.0
     */
    public function set_creation_options(array $options): void
    {
        $this->creation_options = $options;
    }
}