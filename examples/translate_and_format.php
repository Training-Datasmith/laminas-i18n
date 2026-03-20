<?php

declare(strict_types=1);

/**
 * Example: translating strings and formatting numbers/dates with laminas-i18n.
 *
 * Run from the laminas-i18n project root:
 *   php examples/translate_and_format.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Laminas\I18n\Translator\Translator;
use Laminas\I18n\Filter\NumberFormat;
use Laminas\I18n\Filter\NumberParse;

// --- Translator: load from PHP array (no external files needed) ---
$translator = new Translator();
$translator->add_translation_file(
    'phpArray',
    __DIR__ . '/messages_de.php',
    'default',
    'de_DE'
);
$translator->set_locale('de_DE');
$translator->set_fallback_locale('en_US');

// Write a tiny translation file inline for the demo
file_put_contents(
    __DIR__ . '/messages_de.php',
    '<?php return ["Hello, %name%!" => "Hallo, %name%!", "Welcome" => "Willkommen"];'
);

// Reload after writing the file
$translator2 = new Translator();
$translator2->add_translation_file('phpArray', __DIR__ . '/messages_de.php', 'default', 'de_DE');
$translator2->set_locale('de_DE');

echo $translator2->translate('Welcome', 'default', 'de_DE') . "\n";
echo $translator2->translate('Hello, %name%!', 'default', 'de_DE') . "\n";

// Cleanup temp file
@unlink(__DIR__ . '/messages_de.php');
echo "\n";

// --- NumberFormat filter (ICU-based) ---
$formatter = new NumberFormat('de_DE', \NumberFormatter::DECIMAL);
echo "1234567.89 in de_DE: " . $formatter->filter(1234567.89) . "\n";

$enFormatter = new NumberFormat('en_US', \NumberFormatter::CURRENCY);
// Note: currency formatting requires currency symbol in the pattern
$formatter2 = new NumberFormat('en_US', \NumberFormatter::DECIMAL);
echo "1234567.89 in en_US: " . $formatter2->filter(1234567.89) . "\n\n";

// --- NumberParse filter ---
$parser = new NumberParse('de_DE', \NumberFormatter::DECIMAL);
$parsed = $parser->filter('1.234.567,89');
echo "Parsed '1.234.567,89' (de_DE): " . $parsed . "\n";
