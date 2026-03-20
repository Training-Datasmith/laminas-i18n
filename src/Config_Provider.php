<?php

declare (strict_types=1);
namespace Laminas\I18n;

use Laminas\Service_Manager\Factory\Invokable_Factory;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Translator\Translator_Interface;
/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @final
 */
class Config_Provider
{
    /**
     * Return general-purpose laminas-i18n configuration.
     *
     * @return array{
     *     dependencies: ServiceManagerConfiguration,
     *     filters: ServiceManagerConfiguration,
     *     validators: ServiceManagerConfiguration,
     *     view_helpers: ServiceManagerConfiguration,
     *     locale: string|null,
     * }
     */
    public function __invoke(): array
    {
        return ['dependencies' => $this->get_dependency_config(), 'filters' => $this->get_filter_config(), 'validators' => $this->get_validator_config(), 'view_helpers' => $this->get_view_helper_config(), 'locale' => null];
    }
    /**
     * Return application-level dependency configuration.
     *
     * @return ServiceManagerConfiguration
     */
    public function get_dependency_config(): array
    {
        return ['aliases' => [
            'TranslatorPluginManager' => Translator\Loader_Plugin_Manager::class,
            // Legacy Zend Framework aliases
            'Zend\I18n\Translator\TranslatorInterface' => Translator\Translator_Interface::class,
            'Zend\I18n\Translator\LoaderPluginManager' => Translator\Loader_Plugin_Manager::class,
            Geography\Country_Code_List_Interface::class => Geography\Default_Country_Code_List::class,
            Translator_Interface::class => Translator\Translator_Interface::class,
        ], 'factories' => [Translator\Translator_Interface::class => Translator\Translator_Service_Factory::class, Translator\Loader_Plugin_Manager::class => Translator\Loader_Plugin_Manager_Factory::class, Geography\Default_Country_Code_List::class => Geography\Default_Country_Code_List_Factory::class]];
    }
    /**
     * Return laminas-filter configuration.
     *
     * @return ServiceManagerConfiguration
     */
    public function get_filter_config(): array
    {
        return ['aliases' => [
            'alnum' => Filter\Alnum::class,
            'Alnum' => Filter\Alnum::class,
            'alpha' => Filter\Alpha::class,
            'Alpha' => Filter\Alpha::class,
            'numberformat' => Filter\Number_Format::class,
            'numberFormat' => Filter\Number_Format::class,
            'NumberFormat' => Filter\Number_Format::class,
            'numberparse' => Filter\Number_Parse::class,
            'numberParse' => Filter\Number_Parse::class,
            'NumberParse' => Filter\Number_Parse::class,
            // Legacy Zend Framework aliases
            'Zend\I18n\Filter\Alnum' => Filter\Alnum::class,
            'Zend\I18n\Filter\Alpha' => Filter\Alpha::class,
            'Zend\I18n\Filter\NumberFormat' => Filter\Number_Format::class,
            'Zend\I18n\Filter\NumberParse' => Filter\Number_Parse::class,
        ], 'factories' => [Filter\Alnum::class => Invokable_Factory::class, Filter\Alpha::class => Invokable_Factory::class, Filter\Number_Format::class => Invokable_Factory::class, Filter\Number_Parse::class => Invokable_Factory::class]];
    }
    /**
     * Return laminas-validator configuration.
     *
     * @return ServiceManagerConfiguration
     */
    public function get_validator_config(): array
    {
        return ['aliases' => [
            'alnum' => Validator\Alnum::class,
            'Alnum' => Validator\Alnum::class,
            'alpha' => Validator\Alpha::class,
            'Alpha' => Validator\Alpha::class,
            'datetime' => Validator\DateTime::class,
            'dateTime' => Validator\DateTime::class,
            'DateTime' => Validator\DateTime::class,
            'float' => Validator\Is_Float::class,
            'Float' => Validator\Is_Float::class,
            'int' => Validator\Is_Int::class,
            'Int' => Validator\Is_Int::class,
            'isfloat' => Validator\Is_Float::class,
            'isFloat' => Validator\Is_Float::class,
            'IsFloat' => Validator\Is_Float::class,
            'isint' => Validator\Is_Int::class,
            'isInt' => Validator\Is_Int::class,
            'IsInt' => Validator\Is_Int::class,
            'phonenumber' => Validator\Phone_Number::class,
            'phoneNumber' => Validator\Phone_Number::class,
            'PhoneNumber' => Validator\Phone_Number::class,
            'postcode' => Validator\Post_Code::class,
            'postCode' => Validator\Post_Code::class,
            'PostCode' => Validator\Post_Code::class,
            // Legacy Zend Framework aliases
            'Zend\I18n\Validator\Alnum' => Validator\Alnum::class,
            'Zend\I18n\Validator\Alpha' => Validator\Alpha::class,
            'Zend\I18n\Validator\DateTime' => Validator\DateTime::class,
            'Zend\I18n\Validator\IsFloat' => Validator\Is_Float::class,
            'Zend\I18n\Validator\IsInt' => Validator\Is_Int::class,
            'Zend\I18n\Validator\PhoneNumber' => Validator\Phone_Number::class,
            'Zend\I18n\Validator\PostCode' => Validator\Post_Code::class,
        ], 'factories' => [Validator\Alnum::class => Invokable_Factory::class, Validator\Alpha::class => Invokable_Factory::class, Validator\DateTime::class => Invokable_Factory::class, Validator\Is_Float::class => Invokable_Factory::class, Validator\Is_Int::class => Invokable_Factory::class, Validator\Phone_Number::class => Invokable_Factory::class, Validator\Post_Code::class => Invokable_Factory::class]];
    }
    /**
     * Return laminas-view helper configuration.
     *
     * Obsoletes View\HelperConfig.
     *
     * @return ServiceManagerConfiguration
     */
    public function get_view_helper_config(): array
    {
        return ['aliases' => [
            'countryCodeDataList' => View\Helper\Country_Code_Data_List::class,
            'currencyformat' => View\Helper\Currency_Format::class,
            'currencyFormat' => View\Helper\Currency_Format::class,
            'CurrencyFormat' => View\Helper\Currency_Format::class,
            'dateformat' => View\Helper\Date_Format::class,
            'dateFormat' => View\Helper\Date_Format::class,
            'DateFormat' => View\Helper\Date_Format::class,
            'numberformat' => View\Helper\Number_Format::class,
            'numberFormat' => View\Helper\Number_Format::class,
            'NumberFormat' => View\Helper\Number_Format::class,
            'plural' => View\Helper\Plural::class,
            'Plural' => View\Helper\Plural::class,
            'translate' => View\Helper\Translate::class,
            'Translate' => View\Helper\Translate::class,
            'translateplural' => View\Helper\Translate_Plural::class,
            'translatePlural' => View\Helper\Translate_Plural::class,
            'TranslatePlural' => View\Helper\Translate_Plural::class,
            // Legacy Zend Framework aliases
            'Zend\I18n\View\Helper\CurrencyFormat' => View\Helper\Currency_Format::class,
            'Zend\I18n\View\Helper\DateFormat' => View\Helper\Date_Format::class,
            'Zend\I18n\View\Helper\NumberFormat' => View\Helper\Number_Format::class,
            'Zend\I18n\View\Helper\Plural' => View\Helper\Plural::class,
            'Zend\I18n\View\Helper\Translate' => View\Helper\Translate::class,
            'Zend\I18n\View\Helper\TranslatePlural' => View\Helper\Translate_Plural::class,
        ], 'factories' => [View\Helper\Country_Code_Data_List::class => View\Helper\Container\Country_Code_Data_List_Factory::class, View\Helper\Currency_Format::class => Invokable_Factory::class, View\Helper\Date_Format::class => Invokable_Factory::class, View\Helper\Number_Format::class => Invokable_Factory::class, View\Helper\Plural::class => Invokable_Factory::class, View\Helper\Translate::class => Invokable_Factory::class, View\Helper\Translate_Plural::class => Invokable_Factory::class]];
    }
}