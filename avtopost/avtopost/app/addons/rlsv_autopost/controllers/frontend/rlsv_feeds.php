<?php
/**
 * rlsv_autopost — frontend controller: XML-фиды (пассивный PULL).
 *
 * URL:
 *   https://site/index.php?dispatch=rlsv_feeds.dzen
 *   https://site/index.php?dispatch=rlsv_feeds.cian
 *   https://site/index.php?dispatch=rlsv_feeds.domclick
 *
 * Если в настройках задан feed_secret — требуется &secret=XXX.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$allowed = array('dzen', 'cian', 'domclick');

if (in_array($mode, $allowed, true)) {

    // Необязательная защита фидов секретом.
    $secret = (string) Registry::get('addons.rlsv_autopost.feed_secret');
    if ($secret !== '') {
        $given = isset($_REQUEST['secret']) ? $_REQUEST['secret'] : '';
        if ($given !== $secret) {
            fn_rlsv_autopost_log('feed.denied', array('data' => array('format' => $mode)));
            header('HTTP/1.1 403 Forbidden');
            exit('Forbidden');
        }
    }

    $xml = fn_rlsv_autopost_build_feed($mode, CART_LANGUAGE);

    header('Content-Type: application/xml; charset=utf-8');
    echo $xml;
    exit;
}

return array(CONTROLLER_STATUS_NO_PAGE);
