<?php
/**
 * Модуль rlsv_autopost — регистрация хуков (способ CS-Cart 4.x).
 *
 * Хук-функции именуются fn_<addon>_<hook> и лежат в func.php.
 * update_product_post — при сохранении товара: сохраняем пер-товарные настройки
 * и ставим публикации в очередь.
 *
 * Сохранение настроек категории и токенов администратора выполняется
 * непосредственно в backend-контроллерах categories.post.php / profiles.post.php
 * (на POST), а отображение вкладок — там же на GET (navigation.tabs).
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'update_product_post'
);
