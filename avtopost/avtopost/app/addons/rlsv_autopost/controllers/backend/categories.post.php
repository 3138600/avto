<?php
/**
 * rlsv_autopost — вкладка «Автопостинг» в карточке категории.
 * На POST сохраняем пер-категорийный флаг (отдельная таблица, отдельный ключ
 * запроса rlsv_autopost_category — НЕ category_data, чтобы ядро не писало колонку).
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update' && !empty($_REQUEST['category_id']) && isset($_REQUEST['rlsv_autopost_category'])) {
        fn_rlsv_autopost_save_category_settings((int) $_REQUEST['category_id'], $_REQUEST['rlsv_autopost_category']);
        fn_rlsv_autopost_log('category.settings.save', array('data' => array(
            'category_id' => (int) $_REQUEST['category_id'],
        )));
    }
    return array(CONTROLLER_STATUS_OK);
}

if ($mode == 'update') {
    $category_id = !empty($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : 0;

    Registry::set('navigation.tabs.rlsv_autopost', array(
        'title' => __('rlsv_autopost.tab'),
        'js'    => true,
    ));

    Tygh::$app['view']->assign('rlsv_autopost_category', fn_rlsv_autopost_get_category_settings($category_id));
}
