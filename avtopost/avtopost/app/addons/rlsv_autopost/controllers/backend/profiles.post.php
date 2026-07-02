<?php
/**
 * rlsv_autopost — вкладка «Автопостинг» в карточке администратора (токены площадок).
 * На POST сохраняем токены (ключ запроса rlsv_tokens — НЕ user_data).
 * На GET выводим вкладку и предзаполняем поля.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update' && !empty($_REQUEST['user_id'])) {
        $user_id = (int) $_REQUEST['user_id'];

        if (isset($_REQUEST['rlsv_tokens'])) {
            $clean = array();
            foreach ((array) $_REQUEST['rlsv_tokens'] as $k => $v) {
                $v = trim((string) $v);
                if ($v !== '') {
                    $clean[$k] = $v;
                }
            }
            fn_rlsv_autopost_save_user_tokens($user_id, $clean);
            fn_rlsv_autopost_log('tokens.save', array('data' => array(
                'user_id'          => $user_id,
                'platforms_filled' => count($clean),
            )));
        }

        // Привязка товаров/категорий к этому администратору.
        if (isset($_REQUEST['rlsv_autopost_user'])) {
            $bind = $_REQUEST['rlsv_autopost_user'];
            fn_rlsv_autopost_save_user_links(
                $user_id,
                isset($bind['products'])   ? $bind['products']   : array(),
                isset($bind['categories']) ? $bind['categories'] : array()
            );
        }
    }
    return array(CONTROLLER_STATUS_OK);
}

if ($mode == 'update') {
    $user_id = !empty($_REQUEST['user_id']) ? (int) $_REQUEST['user_id'] : 0;

    Registry::set('navigation.tabs.rlsv_autopost', array(
        'title' => __('rlsv_autopost.tab'),
        'js'    => true,
    ));

    Tygh::$app['view']->assign('rlsv_autopost_tokens', fn_rlsv_autopost_get_user_tokens($user_id));
    Tygh::$app['view']->assign('rlsv_autopost_user', fn_rlsv_autopost_get_user_links($user_id));
}
