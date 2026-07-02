<?php
/**
 * rlsv_autopost — КЛИЕНТСКАЯ ЧАСТЬ: подмешивание встроенного редактора контента
 * на страницу товара (products.view) для пользователей, которым этот товар назначен.
 *
 * Сам POST-сохранения обрабатывает контроллер rlsv_account (mode=product),
 * здесь только подготовка данных для шаблона-хука products:product_detail_bottom.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'view') {

    $auth       = $_SESSION['auth'];
    $product_id = !empty($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    if (!empty($auth['user_id']) && $product_id
        && fn_rlsv_autopost_user_can_edit_product((int) $auth['user_id'], $product_id)
    ) {
        $fd = db_get_field(
            "SELECT full_description FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s",
            $product_id, CART_LANGUAGE
        );

        Tygh::$app['view']->assign('rlsv_can_edit', true);
        Tygh::$app['view']->assign('rlsv_csrf', fn_rlsv_autopost_csrf_token());
        Tygh::$app['view']->assign('rlsv_autopost_product', fn_rlsv_autopost_get_product_settings($product_id));
        Tygh::$app['view']->assign('rlsv_full_description', (string) $fd);
    }
}
