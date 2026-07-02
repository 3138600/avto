<?php
/**
 * rlsv_autopost — вкладка «Автопостинг» в карточке товара.
 *
 * На GET:  регистрируем вкладку и передаём пер-товарные настройки + список
 *          привязанных издателей (пользователей, публикующих этот товар).
 * На POST: сохраняем привязку издателей к товару (rlsv_autopost_publishers).
 *          Пер-товарные настройки (флаг автопостинга, video_url) и постановка
 *          в очередь выполняются в хуке update_product_post (func.php).
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update' && !empty($_REQUEST['product_id']) && isset($_REQUEST['rlsv_autopost_publishers'])) {
        fn_rlsv_autopost_save_product_publishers(
            (int) $_REQUEST['product_id'],
            $_REQUEST['rlsv_autopost_publishers']
        );
    }

    return array(CONTROLLER_STATUS_OK);
}

if ($mode == 'update') {
    $product_id = !empty($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    Registry::set('navigation.tabs.rlsv_autopost', array(
        'title' => __('rlsv_autopost.tab'),
        'js'    => true,
    ));

    Tygh::$app['view']->assign('rlsv_autopost_product', fn_rlsv_autopost_get_product_settings($product_id));
    Tygh::$app['view']->assign('rlsv_autopost_product_publishers', fn_rlsv_autopost_get_product_publishers($product_id));
}
