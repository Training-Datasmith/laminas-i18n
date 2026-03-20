<?php

declare (strict_types=1);
namespace Laminas\I18n;

use Laminas\Module_Manager\Module_Manager;
use Laminas\Service_Manager\Service_Manager;
/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @final
 */
class Module
{
    /**
     * Return laminas-i18n configuration for laminas-mvc application.
     *
     * @return array{
     *     filters: ServiceManagerConfiguration,
     *     service_manager: ServiceManagerConfiguration,
     *     validators: ServiceManagerConfiguration,
     *     view_helpers: ServiceManagerConfiguration,
     * }
     */
    public function get_config(): array
    {
        $provider = new Config_Provider();
        return ['filters' => $provider->get_filter_config(), 'service_manager' => $provider->get_dependency_config(), 'validators' => $provider->get_validator_config(), 'view_helpers' => $provider->get_view_helper_config()];
    }
    /**
     * Register a specification for the TranslatorPluginManager with the ServiceListener.
     *
     * @param ModuleManager $moduleManager
     */
    public function init($module_manager): void
    {
        $event = $module_manager->get_event();
        $container = $event->get_param('ServiceManager');
        $service_listener = $container->get('ServiceListener');
        $service_listener->add_service_manager('TranslatorPluginManager', 'translator_plugins', 'Laminas\ModuleManager\Feature\TranslatorPluginProviderInterface', 'getTranslatorPluginConfig');
    }
}