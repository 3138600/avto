<?php
/**
 * rlsv_autopost — backend controller.
 *
 * Режимы:
 *   - cron  : дренаж очереди публикаций (вызывается системным планировщиком).
 *             URL: https://site/index.php?dispatch=rlsv_oauth.cron&cron_password=XXX
 *   - oauth : приём OAuth-редиректов площадок (VK/Pinterest/Instagram/YouTube),
 *             сохранение полученных токенов в профиль текущего администратора.
 *
 * Все этапы пишутся в журнал событий ядра через fn_rlsv_autopost_log().
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'cron') {

    // Крон может вызываться без сессии админа, поэтому защищаемся паролем.
    $password = isset($_REQUEST['cron_password']) ? $_REQUEST['cron_password'] : '';
    $expected = (string) Registry::get('addons.rlsv_autopost.cron_password');

    if ($expected === '' || $password !== $expected) {
        fn_rlsv_autopost_log('cron.denied', array('data' => array('reason' => 'bad cron_password')));
        header('HTTP/1.1 403 Forbidden');
        echo 'Forbidden';
        exit;
    }

    $batch = (int) Registry::get('addons.rlsv_autopost.cron_batch_size');
    if ($batch <= 0) {
        $batch = 20;
    }

    $stat = fn_rlsv_autopost_process_queue($batch);

    // Обновляем метрики Instagram и пишем их в привязанные характеристики товаров.
    // (только если хотя бы одна метрика привязана к характеристике в настройках)
    $ig_stat = fn_rlsv_autopost_refresh_ig_features($batch);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'stat' => $stat, 'ig' => $ig_stat), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($mode == 'oauth') {

    // Приём кода авторизации/токена и сохранение в профиль текущего админа.
    $auth = $_SESSION['auth'];
    if (empty($auth['user_id'])) {
        fn_rlsv_autopost_log('oauth.denied', array('data' => array('reason' => 'no admin session')));
        return array(CONTROLLER_STATUS_DENIED);
    }

    $platform = !empty($_REQUEST['platform']) ? $_REQUEST['platform'] : '';

    // Минимальный приём токена через GET/POST (полный OAuth-обмен code->token
    // зависит от площадки и настраивается на стороне приложения площадки).
    $incoming = array();
    foreach ($_REQUEST as $k => $v) {
        if (strpos($k, 'rlsv_') === 0) {
            $incoming[substr($k, 5)] = $v;
        }
    }

    if (!empty($incoming)) {
        $tokens = fn_rlsv_autopost_get_user_tokens($auth['user_id']);
        $tokens = array_merge($tokens, $incoming);
        fn_rlsv_autopost_save_user_tokens($auth['user_id'], $tokens);

        fn_rlsv_autopost_log('oauth.saved', array('data' => array(
            'user_id' => (int) $auth['user_id'], 'platform' => $platform, 'keys' => array_keys($incoming),
        )));

        fn_set_notification('N', __('notice'), 'Токены сохранены в профиле.');
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'profiles.update?user_id=' . $auth['user_id']);
}

return array(CONTROLLER_STATUS_NO_PAGE);
