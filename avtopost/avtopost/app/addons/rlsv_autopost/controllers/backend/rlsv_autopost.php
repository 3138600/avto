<?php
/**
 * rlsv_autopost — страница статистики публикаций.
 *   dispatch=rlsv_autopost.stats&user_id=X — статистика конкретного администратора.
 *   dispatch=rlsv_autopost.stats            — сводка по всем издателям.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'stats') {

    $user_id = !empty($_REQUEST['user_id']) ? (int) $_REQUEST['user_id'] : 0;

    if ($user_id) {
        $user = db_get_row(
            "SELECT user_id, CONCAT(firstname, ' ', lastname) AS name, email FROM ?:users WHERE user_id = ?i",
            $user_id
        );
        Tygh::$app['view']->assign('rlsv_user_id', $user_id);
        Tygh::$app['view']->assign('rlsv_user', $user);
        Tygh::$app['view']->assign('rlsv_stats', fn_rlsv_autopost_get_user_stats($user_id));
        Tygh::$app['view']->assign('rlsv_links', fn_rlsv_autopost_get_user_links($user_id));
    } else {
        Tygh::$app['view']->assign('rlsv_overview', fn_rlsv_autopost_get_publishers_overview());
    }

    return array(CONTROLLER_STATUS_OK);
}

if ($mode == 'cleanup') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $what = !empty($_REQUEST['what']) ? $_REQUEST['what'] : '';
        $days = !empty($_REQUEST['days']) ? (int) $_REQUEST['days'] : 30;
        if (fn_rlsv_autopost_cleanup($what, $days) !== false) {
            fn_set_notification('N', __('notice'), __('rlsv_autopost.cleanup_done'));
        }
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'rlsv_autopost.stats');
}

return array(CONTROLLER_STATUS_NO_PAGE);
