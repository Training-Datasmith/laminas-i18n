<?php

declare (strict_types=1);
namespace Laminas\I18n\Translator;

use Laminas\Service_Manager\Factory_Interface;
use Laminas\Service_Manager\Service_Locator_Interface;
use Psr\Container\Container_Interface;
/**
 * Translator.
 *
 * @final
 */
class Translator_Service_Factory implements Factory_Interface
{
    /**
     * Create a Translator instance.
     *
     * @param string $requestedName
     * @return Translator
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        // Configure the translator
        $config = $container->get('config');
        $tr_config = $config['translator'] ?? [];
        $translator = Translator::factory($tr_config);
        if ($container->has('TranslatorPluginManager')) {
            $translator->set_plugin_manager($container->get('TranslatorPluginManager'));
        }
        return $translator;
    }
    /**
     * laminas-servicemanager v2 factory for creating Translator instance.
     *
     * @deprecated Since 2.16.0 - This component is no longer compatible with Service Manager v2.
     *             This method will be removed in version 3.0
     *
     * Proxies to `__invoke()`.
     *
     * @return Translator
     */
    public function create_service(Service_Locator_Interface $service_locator)
    {
        return $this($service_locator, Translator::class);
    }
}