<?php
/**
 * rlsv_autopost — КЛИЕНТСКАЯ ЧАСТЬ: вкладка «Автопостинг» в личном кабинете покупателя.
 *
 * В теме витрины страница профиля (views/profiles/update.tpl) добавляет вкладки
 * через хук {hook name="profiles:tabs"} и рисует их из Registry navigation.tabs.
 * Здесь на GET (mode=update) мы регистрируем вкладку и передаём в шаблон токены
 * пользователя и CSRF-токен. Сохранение полей выполняет контроллер rlsv_account.settings
 * (форма во вкладке отправляется туда и возвращает пользователя на эту же вкладку).
 *
 * Важно: сохранение НЕ выполняется здесь, чтобы не пересекаться со штатным
 * сохранением профиля (dispatch=profiles.update) и не затирать поля пользователя.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'update') {

    $auth = $_SESSION['auth'];

    if (!empty($auth['user_id'])) {
        $user_id = (int) $auth['user_id'];

        // Регистрируем вкладку (так же, как ядро регистрирует general/usergroups).
        Registry::set('navigation.tabs.rlsv_autopost', array(
            'title' => __('rlsv_autopost.account_panel'),
            'js'    => true,
        ));

        Tygh::$app['view']->assign('rlsv_autopost_tokens', fn_rlsv_autopost_get_user_tokens($user_id));
        Tygh::$app['view']->assign('rlsv_csrf', fn_rlsv_autopost_csrf_token());
    }
}
