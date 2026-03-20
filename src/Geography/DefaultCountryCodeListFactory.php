<?php

declare (strict_types=1);
namespace Laminas\I18n\Geography;

/**
 * @internal
 *
 * @psalm-internal Laminas\I18n
 * @psalm-internal LaminasTest\I18n
 */
final readonly class Default_Country_Code_List_Factory
{
    public function __invoke(): Default_Country_Code_List
    {
        return Default_Country_Code_List::create();
    }
}