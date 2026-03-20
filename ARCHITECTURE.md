# Architecture: laminas-i18n

## Purpose
Laminas i18n — internationalisation support for PHP. Provides translation (Gettext, PhpArray, Ini, CSV, TMX, XLIFF), locale-aware formatting (numbers, currencies, dates, times), and validators (phone numbers for 200+ countries, PostCode, etc.).

## Directory Structure
```
src/
  Translator/
    Translator.php                    # Primary translation service — translate(), translatePlural()
    Loader_Plugin_Manager.php         # Plugin manager for translation file format loaders
    Loader/                           # Gettext, PhpArray, Ini, Csv, Tmx, Xliff loaders
  Filter/
    Alnum.php / Alpha.php / NumberFormat.php / NumberParse.php
    StringToWord.php (locale-aware)
  Validator/
    PhoneNumber.php                   # Validates phone numbers using per-country regex data
    PhoneNumber/                      # ~200 country-specific phone number data files (AC.php, US.php, etc.)
    PostCode.php / IsFloat.php / IsInt.php / DateTime.php
  View/Helper/
    Translate.php / Translate_Plural.php
    Date_Format.php / Time_Format.php / Date_Time_Format.php
    Number_Format.php / Currency_Format.php / Plural.php
    Abstract_Helper.php / Abstract_Helper_Factory.php
  Exception/                         # Typed exceptions
  Module.php / Config_Provider.php
```

## Key Design Decisions
- **Format-agnostic translation loader** — the `Loader_Plugin_Manager` dispatches to the appropriate loader by alias (e.g., `'gettext'`, `'phparray'`). Adding a new format requires only a `Loader_Interface` implementation and plugin registration.
- **ICU-backed formatting** — number, currency, and date formatters delegate to PHP's `intl` extension (`NumberFormatter`, `IntlDateFormatter`).
- **Per-country phone data** — phone number validation uses ~200 small PHP files (one per ISO 3166-1 alpha-2 code) rather than a monolithic libphonenumber. This keeps the dependency light but limits support to regex-based matching.
- **View helper integration** — all formatting/translation utilities have corresponding Laminas View helpers for direct use in templates.

## Extension Points
- Implement `FileLoaderInterface` to add a new translation file format.
- Register a custom locale-based plural rule via `Translator::addTranslationFilePattern()`.
- Implement `ValidatorInterface` to add a custom locale-aware validator.

## Dependency Flow
```
Translator::translate($message, $textDomain, $locale)
  └─ Loader_Plugin_Manager → specific Loader (Gettext/PhpArray/…)
       └─ loaded translations map → translated string

PhoneNumber validator
  └─ loads src/Validator/PhoneNumber/{CC}.php → regex patterns
       └─ preg_match() → valid/invalid
```
