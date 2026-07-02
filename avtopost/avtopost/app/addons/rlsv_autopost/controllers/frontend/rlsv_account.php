<?php
/**
 * rlsv_autopost — КЛИЕНТСКАЯ ЧАСТЬ (витрина): личный кабинет издателя.
 *
 * Режимы (dispatch=rlsv_account.<mode>):
 *   - settings : GET — форма настроек API и прокси; POST — сохранение (CSRF + allowlist).
 *   - stats    : GET — аналитика публикаций пользователя (вкладки по каналам и по товарам).
 *   - product  : GET — редактор контента товара; POST — сохранение «подробного описания»
 *                и пер-товарных настроек (CSRF + проверка прав + санитайз HTML).
 *
 * Все страницы требуют авторизованного пользователя. Пользователь видит и меняет
 * ТОЛЬКО свои данные; товары — только те, что назначены ему администратором.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$auth = $_SESSION['auth'];

// Доступ к кабинету — только авторизованным.
if (empty($auth['user_id'])) {
    return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
}
$user_id = (int) $auth['user_id'];

/* ---------------------------------------------------------------------- *
 *  НАСТРОЙКИ API / ПРОКСИ
 * ---------------------------------------------------------------------- */
if ($mode == 'settings') {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        // Куда вернуть после сохранения: во вкладку «Автопостинг» в карточке
        // пользователя (rlsv_return=profile) или на отдельную страницу настроек.
        $return_dispatch = (isset($_REQUEST['rlsv_return']) && $_REQUEST['rlsv_return'] === 'profile')
            ? 'profiles.update?selected_section=rlsv_autopost'
            : 'rlsv_account.settings';

        if (!fn_rlsv_autopost_verify_csrf()) {
            fn_set_notification('E', __('error'), __('rlsv_autopost.csrf_failed'));
            return array(CONTROLLER_STATUS_REDIRECT, $return_dispatch);
        }

        $incoming = (isset($_REQUEST['rlsv_tokens']) && is_array($_REQUEST['rlsv_tokens']))
            ? $_REQUEST['rlsv_tokens'] : array();

        // Принимаем строго поля из белого списка.
        $clean = array();
        foreach (fn_rlsv_autopost_token_field_whitelist() as $key) {
            if (isset($incoming[$key])) {
                $value = trim((string) $incoming[$key]);
                if ($value !== '') {
                    $clean[$key] = $value;
                }
            }
        }

        // Валидация прокси.
        if (isset($clean['proxy'])) {
            $normalized = fn_rlsv_autopost_validate_proxy($clean['proxy']);
            if ($normalized === false) {
                unset($clean['proxy']);
                fn_set_notification('W', __('warning'), __('rlsv_autopost.proxy_invalid'));
            } else {
                $clean['proxy'] = $normalized;
            }
        }

        fn_rlsv_autopost_save_user_tokens($user_id, $clean);
        fn_rlsv_autopost_log('account.settings.save', array('data' => array(
            'user_id' => $user_id, 'fields_filled' => count($clean),
        )));
        fn_set_notification('N', __('notice'), __('rlsv_autopost.settings_saved'));

        return array(CONTROLLER_STATUS_REDIRECT, $return_dispatch);
    }

    Tygh::$app['view']->assign('rlsv_tokens', fn_rlsv_autopost_get_user_tokens($user_id));
    Tygh::$app['view']->assign('rlsv_csrf', fn_rlsv_autopost_csrf_token());
    Tygh::$app['view']->assign('rlsv_platforms', fn_rlsv_autopost_get_enabled_platforms());

    return array(CONTROLLER_STATUS_OK);
}

/* ---------------------------------------------------------------------- *
 *  АНАЛИТИКА ПУБЛИКАЦИЙ
 * ---------------------------------------------------------------------- */
if ($mode == 'stats') {

    $stats      = fn_rlsv_autopost_get_user_stats($user_id);
    $by_channel = fn_rlsv_autopost_get_user_stats_by_channel($user_id);
    $platforms  = fn_rlsv_autopost_get_enabled_platforms();

    $ch_dash = array();
    foreach ($platforms as $pf) {
        $ch_dash[$pf] = fn_rlsv_autopost_build_channel_dashboard(isset($by_channel[$pf]) ? $by_channel[$pf] : array());
    }

    Tygh::$app['view']->assign('rlsv_stats', $stats);
    Tygh::$app['view']->assign('rlsv_by_channel', $by_channel);
    Tygh::$app['view']->assign('rlsv_by_product', fn_rlsv_autopost_get_user_product_stats($user_id));
    Tygh::$app['view']->assign('rlsv_platforms', $platforms);
    Tygh::$app['view']->assign('rlsv_ov_dash', fn_rlsv_autopost_build_overview_dashboard($stats, $platforms));
    Tygh::$app['view']->assign('rlsv_ch_dash', $ch_dash);

    return array(CONTROLLER_STATUS_OK);
}

/* ---------------------------------------------------------------------- *
 *  РАСШИРЕННАЯ АНАЛИТИКА INSTAGRAM (лента / Reels / Stories)
 *  Отдельная страница: тянет метрики из Graph API по запросу пользователя,
 *  чтобы не замедлять обычную страницу аналитики.
 * ---------------------------------------------------------------------- */
if ($mode == 'instagram') {

    // Числа берутся из характеристик привязанных товаров (ID характеристик — из настроек).
    Tygh::$app['view']->assign('rlsv_ig', fn_rlsv_autopost_get_feature_analytics($user_id));

    return array(CONTROLLER_STATUS_OK);
}

/* ---------------------------------------------------------------------- *
 *  РЕДАКТОР КОНТЕНТА ТОВАРА (подробное описание + пер-товарные настройки)
 * ---------------------------------------------------------------------- */
if ($mode == 'product') {

    $product_id = !empty($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    // Право редактирования назначается администратором (привязки). Иначе — 403.
    if (!$product_id || !fn_rlsv_autopost_user_can_edit_product($user_id, $product_id)) {
        return array(CONTROLLER_STATUS_DENIED);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        if (!fn_rlsv_autopost_verify_csrf()) {
            fn_set_notification('E', __('error'), __('rlsv_autopost.csrf_failed'));
            return array(CONTROLLER_STATUS_REDIRECT, 'rlsv_account.product?product_id=' . $product_id);
        }

        if (isset($_REQUEST['rlsv_full_description'])) {
            fn_rlsv_autopost_save_product_full_description($product_id, CART_LANGUAGE, $_REQUEST['rlsv_full_description']);
        }

        if (isset($_REQUEST['rlsv_autopost_product'])) {
            fn_rlsv_autopost_save_product_settings($product_id, $_REQUEST['rlsv_autopost_product']);
        }

        fn_set_notification('N', __('notice'), __('rlsv_autopost.product_saved'));

        // Безопасный редирект (без открытого редиректа): только на свои страницы.
        $return = (isset($_REQUEST['rlsv_return']) && $_REQUEST['rlsv_return'] === 'card')
            ? 'products.view?product_id=' . $product_id
            : 'rlsv_account.product?product_id=' . $product_id;

        return array(CONTROLLER_STATUS_REDIRECT, $return);
    }

    $product = fn_get_product_data($product_id, $auth, CART_LANGUAGE);
    $fd = db_get_field(
        "SELECT full_description FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s",
        $product_id, CART_LANGUAGE
    );

    Tygh::$app['view']->assign('rlsv_product', $product);
    Tygh::$app['view']->assign('rlsv_full_description', (string) $fd);
    Tygh::$app['view']->assign('rlsv_autopost_product', fn_rlsv_autopost_get_product_settings($product_id));
    Tygh::$app['view']->assign('rlsv_csrf', fn_rlsv_autopost_csrf_token());

    return array(CONTROLLER_STATUS_OK);
}

return array(CONTROLLER_STATUS_NO_PAGE);
