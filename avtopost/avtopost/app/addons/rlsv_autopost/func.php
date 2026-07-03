<?php
/**
 * Модуль rlsv_autopost для CS-Cart 4.9.2 / PHP 7.x
 * Бизнес-логика: фильтрация по категории, очередь, отправка на площадки,
 * генерация XML-фидов и сквозное логирование через ядро (fn_log_event).
 *
 * Архитектурное примечание:
 *  - Хук update_product_post НЕ ходит в сеть. Он только проверяет товар и
 *    ставит задачи в очередь (?:rlsv_autopost_queue).
 *  - Реальные API-вызовы (включая загрузку видео на YouTube/RuTube) делает
 *    крон (controllers/backend/rlsv_oauth.php -> fn_rlsv_autopost_process_queue).
 *    Иначе сохранение товара в админке будет «висеть» и падать по таймауту.
 */

use Tygh\Registry;
use Tygh\Http;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/* ======================================================================
 *  ЛОГИРОВАНИЕ (ядро CS-Cart -> Администрирование -> Журнал событий)
 * ====================================================================== */

/**
 * Единая точка логирования модуля. Пишем в стандартный журнал ядра
 * через fn_log_event('requests','http', ...), как требует ТЗ, чтобы записи
 * были видны в разделе «Журнал событий».
 *
 * @param string $event  Короткий код этапа (например, 'queue.add', 'api.vk')
 * @param array  $data   Произвольные детали (url, request, response, error...)
 */
function fn_rlsv_autopost_log($event, array $data = array())
{
    if (!function_exists('fn_log_event')) {
        return;
    }

    // Синтетический URL делает запись узнаваемой в списке HTTP-запросов журнала.
    $payload = array(
        'url'      => 'rlsv_autopost://' . $event,
        'data'     => isset($data['request'])  ? $data['request']  : (isset($data['data']) ? $data['data'] : $data),
        'response' => isset($data['response']) ? $data['response'] : null,
    );

    if (isset($data['error']) && $data['error'] !== '' && $data['error'] !== null) {
        $payload['response'] = 'ERROR: ' . (is_scalar($data['error']) ? $data['error'] : json_encode($data['error']));
    }

    fn_log_event('requests', 'http', $payload);
}

/* ======================================================================
 *  ХУК СОХРАНЕНИЯ ТОВАРА  (вызывается из init.php)
 * ====================================================================== */

/**
 * Точка входа из хука update_product_post.
 * Проверяет привязку к целевой категории и ставит задачи в очередь
 * для каждого администратора целевой группы.
 */
function fn_rlsv_autopost_update_product_post($product_data, $product_id, $lang_code, $create = false)
{
    $product_id = (int) $product_id;
    if (!$product_id) {
        return;
    }

    // 0) Сохраняем пер-товарные настройки из вкладки карточки товара (если пришли).
    if (isset($_REQUEST['rlsv_autopost_product'])) {
        fn_rlsv_autopost_save_product_settings($product_id, $_REQUEST['rlsv_autopost_product']);
    }

    // 0a) Уважение пер-товарного флага: если товар явно исключён — не публикуем.
    $product_settings = fn_rlsv_autopost_get_product_settings($product_id);
    if ($product_settings['autopost'] !== 'Y') {
        fn_rlsv_autopost_log('product.disabled', array(
            'data' => array('product_id' => $product_id, 'reason' => 'autopost disabled on product card'),
        ));
        return;
    }

    // 1) Определяем издателей: пользователи, привязанные к этому товару
    //    (напрямую или через привязанную категорию). Если явных привязок нет —
    //    функция сама применит легаси-фолбэк (общая группа + фильтр категорий).
    $publishers = fn_rlsv_autopost_get_publishers_for_product($product_id);
    if (empty($publishers)) {
        fn_rlsv_autopost_log('publishers.empty', array(
            'data' => array('product_id' => $product_id, 'reason' => 'no user is bound to this product/category'),
        ));
        return;
    }

    // 2) Учитываем avail_since: если дата доступности в будущем — публикуем позже.
    $run_after = TIME;
    if (!empty($product_data['avail_since'])) {
        $avail = is_numeric($product_data['avail_since'])
            ? (int) $product_data['avail_since']
            : (int) fn_parse_date($product_data['avail_since']);
        if ($avail > TIME) {
            $run_after = $avail;
        }
    }
    $delay = (int) Registry::get('addons.rlsv_autopost.post_delay_minutes');
    if ($delay > 0) {
        $run_after += $delay * 60;
    }

    // 3) Снимок «карточки» на момент сохранения — кладём в payload задачи,
    //    чтобы крон отправил именно то, что было сохранено.
    $card = fn_rlsv_autopost_build_card($product_id, $lang_code);
    if (empty($card)) {
        fn_rlsv_autopost_log('card.empty', array('data' => array('product_id' => $product_id)));
        return;
    }

    // 4) Для каждого привязанного пользователя — ставим задачи по его площадкам.
    $platforms = fn_rlsv_autopost_get_enabled_platforms();

    foreach ($publishers as $user_id) {
        $tokens = fn_rlsv_autopost_get_user_tokens($user_id);
        if (empty($tokens)) {
            continue;
        }
        foreach ($platforms as $platform) {
            // Ставим задачу только если у пользователя есть токен под площадку.
            if (!fn_rlsv_autopost_user_has_tokens($tokens, $platform)) {
                continue;
            }
            fn_rlsv_autopost_enqueue($product_id, (int) $user_id, $platform, $card, $run_after);
        }
    }
}

/* ======================================================================
 *  ФИЛЬТРАЦИЯ ПО КАТЕГОРИИ
 * ====================================================================== */

function fn_rlsv_autopost_is_in_target_category($product_id)
{
    $cats = array();

    $target = (int) Registry::get('addons.rlsv_autopost.target_category_id');
    if ($target) {
        $cats[] = $target;
    }

    // Категории, отмеченные «Публиковать товары этой категории» в карточке категории.
    $cats = array_unique(array_merge($cats, fn_rlsv_autopost_get_flagged_categories()));

    if (empty($cats)) {
        // Ни общая категория, ни помеченные не заданы — фильтр выключен.
        return true;
    }

    $exists = db_get_field(
        "SELECT product_id FROM ?:products_categories WHERE product_id = ?i AND category_id IN (?n)",
        $product_id, $cats
    );

    return !empty($exists);
}

/* ======================================================================
 *  ПОЛУЧЕНИЕ ЦЕЛЕВЫХ ПОЛЬЗОВАТЕЛЕЙ И ТОКЕНОВ
 * ====================================================================== */

/**
 * ID целевой группы пользователей-издателей из настроек модуля (0 — не задана).
 */
function fn_rlsv_autopost_target_usergroup_id()
{
    return (int) Registry::get('addons.rlsv_autopost.target_usergroup_id');
}

/**
 * Оставляет из списка user_id только АКТИВНЫХ участников целевой группы
 * (настройка target_usergroup_id). Так гарантируется, что автопостят только
 * обычные пользователи из нужной группы, независимо от того, как они привязаны
 * (напрямую к товару, через категорию или фолбэком). Тип аккаунта (админ/покупатель)
 * значения не имеет — важно членство в группе. Если группа в настройках не задана,
 * список возвращается без изменений (гейт по группе отключён).
 *
 * @param  int[] $user_ids
 * @return int[]
 */
function fn_rlsv_autopost_filter_group_members(array $user_ids)
{
    $user_ids = array_filter(array_map('intval', $user_ids));
    if (empty($user_ids)) {
        return array();
    }

    $usergroup_id = fn_rlsv_autopost_target_usergroup_id();
    if (!$usergroup_id) {
        return array_values(array_unique($user_ids));
    }

    $allowed = db_get_fields(
        "SELECT ul.user_id
           FROM ?:usergroup_links AS ul
           INNER JOIN ?:users AS u ON u.user_id = ul.user_id
          WHERE ul.usergroup_id = ?i AND ul.status = 'A' AND u.status = 'A'
            AND ul.user_id IN (?n)",
        $usergroup_id, $user_ids
    );

    return array_map('intval', (array) $allowed);
}

/**
 * Возвращает [user_id => tokens(array)] для ИЗДАТЕЛЕЙ — обычных пользователей
 * (НЕ обязательно администраторов), состоящих в целевой группе (target_usergroup_id).
 * Используется как фолбэк, когда у товара нет явных привязок издателей.
 * Токены каждого пользователя хранятся индивидуально в ?:rlsv_autopost_tokens по user_id.
 * Если целевая группа в настройках не задана — фолбэк пуст (публикуют только
 * явно привязанные пользователи).
 */
function fn_rlsv_autopost_get_target_users()
{
    $usergroup_id = fn_rlsv_autopost_target_usergroup_id();
    if (!$usergroup_id) {
        return array();
    }

    // Активные участники целевой группы с активной учётной записью.
    // НИКАКОГО ограничения по user_type — это могут быть обычные покупатели.
    $uids = db_get_fields(
        "SELECT ul.user_id
           FROM ?:usergroup_links AS ul
           INNER JOIN ?:users AS u ON u.user_id = ul.user_id
          WHERE ul.usergroup_id = ?i AND ul.status = 'A' AND u.status = 'A'",
        $usergroup_id
    );
    if (empty($uids)) {
        return array();
    }

    $rows = db_get_hash_single_array(
        "SELECT t.user_id, t.tokens FROM ?:rlsv_autopost_tokens AS t
          WHERE t.user_id IN (?n)",
        array('user_id', 'tokens'),
        $uids
    );

    $result = array();
    foreach ($rows as $user_id => $json) {
        $tokens = fn_rlsv_autopost_decode_tokens($json);
        if (!empty($tokens)) {
            $result[(int) $user_id] = $tokens;
        }
    }

    return $result;
}

/**
 * Ключ шифрования токенов «в покое».
 * Приоритет: настройка модуля token_crypt_key -> ядровой секрет -> derived.
 * Возвращает 32 байта (для AES-256) либо '' если ключ собрать не из чего.
 */
function fn_rlsv_autopost_crypt_key()
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }

    $material = (string) Registry::get('addons.rlsv_autopost.token_crypt_key');
    if ($material === '') {
        $material = (string) Registry::get('config.crypt_key'); // если есть в ядре
    }
    if ($material === '') {
        // Деривация из install-специфичных значений (лучше, чем хранить в открытом виде).
        $material = (string) Registry::get('config.db_name')
                  . '|' . (string) Registry::get('addons.rlsv_autopost.cron_password')
                  . '|' . (string) Registry::get('addons.rlsv_autopost.feed_secret');
        $material = trim($material, '|');
    }

    $key = ($material !== '') ? hash('sha256', $material, true) : '';

    return $key;
}

/**
 * Шифрует массив токенов для хранения. При наличии ключа и openssl — AES-256-CBC,
 * иначе деградирует до JSON (как было раньше), чтобы установка не падала.
 */
function fn_rlsv_autopost_encode_tokens(array $tokens)
{
    $json = json_encode($tokens, JSON_UNESCAPED_UNICODE);

    $key = fn_rlsv_autopost_crypt_key();
    if ($key !== '' && function_exists('openssl_encrypt')) {
        $iv = function_exists('random_bytes') ? random_bytes(16) : openssl_random_pseudo_bytes(16);
        $ct = openssl_encrypt($json, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($ct !== false) {
            return 'enc:v1:' . base64_encode($iv . $ct);
        }
    }

    return $json; // легаси-режим (открытый JSON)
}

/**
 * Декодирует значение из БД: расшифровывает enc:v1:… либо читает легаси-JSON.
 * Обратная совместимость со старыми (незашифрованными) записями гарантирована.
 */
function fn_rlsv_autopost_decode_tokens($json)
{
    if (empty($json)) {
        return array();
    }

    if (strpos($json, 'enc:v1:') === 0) {
        $key = fn_rlsv_autopost_crypt_key();
        $raw = base64_decode(substr($json, 7), true);
        if ($key !== '' && $raw !== false && strlen($raw) > 16 && function_exists('openssl_decrypt')) {
            $iv  = substr($raw, 0, 16);
            $ct  = substr($raw, 16);
            $pt  = openssl_decrypt($ct, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($pt !== false) {
                $data = json_decode($pt, true);

                return is_array($data) ? $data : array();
            }
        }

        return array(); // ключ сменился/повреждено — не роняем процесс
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : array();
}

function fn_rlsv_autopost_get_user_tokens($user_id)
{
    $json = db_get_field("SELECT tokens FROM ?:rlsv_autopost_tokens WHERE user_id = ?i", (int) $user_id);

    return fn_rlsv_autopost_decode_tokens($json);
}

function fn_rlsv_autopost_save_user_tokens($user_id, array $tokens)
{
    db_query("REPLACE INTO ?:rlsv_autopost_tokens ?e", array(
        'user_id' => (int) $user_id,
        'tokens'  => fn_rlsv_autopost_encode_tokens($tokens),
    ));
}

/* ----------------------------------------------------------------------
 *  Пер-товарные настройки (вкладка в карточке товара)
 * -------------------------------------------------------------------- */

function fn_rlsv_autopost_get_product_settings($product_id)
{
    $row = db_get_row("SELECT * FROM ?:rlsv_autopost_products WHERE product_id = ?i", (int) $product_id);
    if (empty($row)) {
        // по умолчанию публикуем как «Фото и видео»
        return array(
            'autopost'     => 'Y',
            'video_url'    => '',
            'pub_media'    => 'Y',
            'pub_carousel' => 'N',
            'pub_stories'  => 'N',
        );
    }

    // Значения по умолчанию для колонок, которых может не быть на старой схеме.
    $row += array('pub_media' => 'Y', 'pub_carousel' => 'N', 'pub_stories' => 'N');

    return $row;
}

function fn_rlsv_autopost_save_product_settings($product_id, $data)
{
    // Самовосстановление схемы: на базах, где ещё нет колонок pub_* (модуль обновлён
    // без переустановки/включения), REPLACE INTO с этими колонками молча не сработал бы,
    // и настройки товара не сохранялись бы. Гарантируем наличие таблицы/колонок один раз за запрос.
    static $schema_checked = false;
    if (!$schema_checked) {
        if (function_exists('fn_rlsv_autopost_ensure_schema')) {
            fn_rlsv_autopost_ensure_schema();
        }
        $schema_checked = true;
    }

    // Слияние с текущими значениями: поля, которых нет в форме, не сбрасываются.
    $cur = fn_rlsv_autopost_get_product_settings($product_id);

    $yn = function ($key, $default) use ($data, $cur) {
        if (array_key_exists($key, $data)) {
            return ($data[$key] == 'Y') ? 'Y' : 'N';
        }
        return isset($cur[$key]) ? $cur[$key] : $default;
    };

    db_query("REPLACE INTO ?:rlsv_autopost_products ?e", array(
        'product_id'   => (int) $product_id,
        'autopost'     => $yn('autopost', 'Y'),
        'video_url'    => array_key_exists('video_url', $data)
            ? trim((string) $data['video_url'])
            : (isset($cur['video_url']) ? $cur['video_url'] : ''),
        'pub_media'    => $yn('pub_media', 'Y'),
        'pub_carousel' => $yn('pub_carousel', 'N'),
        'pub_stories'  => $yn('pub_stories', 'N'),
    ));
}

/* ----------------------------------------------------------------------
 *  Пер-категорийные настройки (вкладка в карточке категории)
 * -------------------------------------------------------------------- */

function fn_rlsv_autopost_get_category_settings($category_id)
{
    $row = db_get_row("SELECT * FROM ?:rlsv_autopost_categories WHERE category_id = ?i", (int) $category_id);
    if (empty($row)) {
        return array('autopost' => 'N'); // по умолчанию категория не добавляется в фильтр
    }

    return $row;
}

function fn_rlsv_autopost_save_category_settings($category_id, $data)
{
    db_query("REPLACE INTO ?:rlsv_autopost_categories ?e", array(
        'category_id' => (int) $category_id,
        'autopost'    => (!empty($data['autopost']) && $data['autopost'] == 'Y') ? 'Y' : 'N',
    ));
}

function fn_rlsv_autopost_get_flagged_categories()
{
    $ids = db_get_fields("SELECT category_id FROM ?:rlsv_autopost_categories WHERE autopost = 'Y'");

    return array_map('intval', (array) $ids);
}

/* ----------------------------------------------------------------------
 *  Привязка пользователя к товарам и категориям (на странице профиля)
 * -------------------------------------------------------------------- */

/**
 * Возвращает привязки администратора:
 *   ['products' => [id,...], 'categories' => [id,...]]
 */
function fn_rlsv_autopost_get_user_links($user_id)
{
    $rows = db_get_array(
        "SELECT link_type, object_id FROM ?:rlsv_autopost_user_links WHERE user_id = ?i",
        (int) $user_id
    );

    $links = array('products' => array(), 'categories' => array());
    foreach ($rows as $r) {
        if ($r['link_type'] === 'category') {
            $links['categories'][] = (int) $r['object_id'];
        } else {
            $links['products'][] = (int) $r['object_id'];
        }
    }

    return $links;
}

/**
 * Сохраняет привязки администратора (полная перезапись).
 * $products / $categories могут прийти строкой "1,2,3" или массивом.
 */
function fn_rlsv_autopost_save_user_links($user_id, $products, $categories)
{
    $user_id = (int) $user_id;
    if (!$user_id) {
        return;
    }

    $normalize = function ($value) {
        if (!is_array($value)) {
            $value = strlen((string) $value) ? explode(',', (string) $value) : array();
        }

        return array_filter(array_unique(array_map('intval', $value)));
    };

    $products   = $normalize($products);
    $categories = $normalize($categories);

    db_query("DELETE FROM ?:rlsv_autopost_user_links WHERE user_id = ?i", $user_id);

    $data = array();
    foreach ($products as $pid) {
        $data[] = array('user_id' => $user_id, 'link_type' => 'product', 'object_id' => $pid);
    }
    foreach ($categories as $cid) {
        $data[] = array('user_id' => $user_id, 'link_type' => 'category', 'object_id' => $cid);
    }
    foreach ($data as $row) {
        db_query("INSERT INTO ?:rlsv_autopost_user_links ?e", $row);
    }

    fn_rlsv_autopost_log('user_links.save', array('data' => array(
        'user_id'    => $user_id,
        'products'   => count($products),
        'categories' => count($categories),
    )));
}

/**
 * Кто публикует данный товар: пользователи, к которым он привязан
 * напрямую (link_type=product) или через привязанную категорию (link_type=category).
 * Если явных привязок нет — используется легаси-механизм (общая группа + категория).
 *
 * @return int[] список user_id
 */
function fn_rlsv_autopost_get_publishers_for_product($product_id)
{
    $product_id = (int) $product_id;

    // 1) Прямая привязка товара к пользователю.
    $direct = db_get_fields(
        "SELECT user_id FROM ?:rlsv_autopost_user_links
         WHERE link_type = 'product' AND object_id = ?i",
        $product_id
    );

    // 2) Привязка через категорию, в которой состоит товар.
    $by_category = db_get_fields(
        "SELECT DISTINCT l.user_id
         FROM ?:rlsv_autopost_user_links AS l
         INNER JOIN ?:products_categories AS pc
                 ON pc.category_id = l.object_id AND pc.product_id = ?i
         WHERE l.link_type = 'category'",
        $product_id
    );

    $publishers = array_unique(array_map('intval', array_merge((array) $direct, (array) $by_category)));

    // 3) Легаси-фолбэк: если явных привязок нет — общая группа + общий/помеченный фильтр категорий.
    if (empty($publishers) && fn_rlsv_autopost_is_in_target_category($product_id)) {
        $legacy = fn_rlsv_autopost_get_target_users(); // [user_id => tokens]
        $publishers = array_map('intval', array_keys($legacy));
    }

    // Требование: автопостит только обычный пользователь, состоящий в целевой группе
    // (target_usergroup_id). Гейт применяется ко ВСЕМ источникам издателей (прямая
    // привязка/через категорию/фолбэк). Если группа не задана — гейт отключён.
    $publishers = fn_rlsv_autopost_filter_group_members($publishers);

    return $publishers;
}

/**
 * Список площадок активного PUSH, который обслуживает очередь.
 * (PULL-фиды — Дзен/Циан/Домклик — работают по URL и в очереди не нуждаются.)
 */
function fn_rlsv_autopost_get_enabled_platforms()
{
    $all = array('vk', 'instagram', 'telegram', 'ok', 'pinterest', 'max', 'youtube', 'rutube');

    $enabled = Registry::get('addons.rlsv_autopost.enabled_platforms');
    if (empty($enabled) || !is_array($enabled)) {
        return $all;
    }

    // multiple_checkboxes может храниться как ['vk'=>'Y',...] или как ['vk','telegram',...]
    $result = array();
    foreach ($all as $p) {
        if ((isset($enabled[$p]) && $enabled[$p] == 'Y') || in_array($p, $enabled, true)) {
            $result[] = $p;
        }
    }

    return empty($result) ? $all : $result;
}

/**
 * Проверяет, что у пользователя достаточно токенов под конкретную площадку.
 */
function fn_rlsv_autopost_user_has_tokens($tokens, $platform)
{
    $required = array(
        'vk'        => array('vk_access_token', 'vk_owner_id'),
        'instagram' => array('ig_access_token', 'ig_user_id'),
        'telegram'  => array('tg_bot_token', 'tg_chat_id'),
        'ok'        => array('ok_access_token', 'ok_application_key', 'ok_session_secret_key'),
        'pinterest' => array('pin_access_token', 'pin_board_id'),
        'max'       => array('max_webhook_url'),
        'youtube'   => array('yt_client_id', 'yt_client_secret', 'yt_refresh_token'),
        'rutube'    => array('rt_access_token'),
    );

    if (empty($required[$platform])) {
        return false;
    }
    foreach ($required[$platform] as $key) {
        if (empty($tokens[$key])) {
            return false;
        }
    }

    return true;
}

/* ======================================================================
 *  ОЧЕРЕДЬ
 * ====================================================================== */

function fn_rlsv_autopost_enqueue($product_id, $user_id, $platform, array $card, $run_after)
{
    // Защита от дублей: одна и та же пара (товар, пользователь, площадка)
    // в статусе pending обновляется, а не плодит задачи.
    $existing = db_get_field(
        "SELECT queue_id FROM ?:rlsv_autopost_queue
         WHERE product_id = ?i AND user_id = ?i AND platform = ?s AND status = 'pending'",
        $product_id, $user_id, $platform
    );

    $data = array(
        'product_id' => (int) $product_id,
        'user_id'    => (int) $user_id,
        'platform'   => $platform,
        'status'     => 'pending',
        'payload'    => json_encode($card, JSON_UNESCAPED_UNICODE),
        'attempts'   => 0,
        'error'      => '',
        'run_after'  => (int) $run_after,
        'created_at' => TIME,
    );

    if ($existing) {
        db_query("UPDATE ?:rlsv_autopost_queue SET ?u WHERE queue_id = ?i", $data, $existing);
        $queue_id = $existing;
    } else {
        $queue_id = db_query("INSERT INTO ?:rlsv_autopost_queue ?e", $data);
    }

    fn_rlsv_autopost_log('queue.add', array('data' => array(
        'queue_id'   => $queue_id,
        'product_id' => $product_id,
        'user_id'    => $user_id,
        'platform'   => $platform,
        'run_after'  => date('Y-m-d H:i:s', $run_after),
    )));

    return $queue_id;
}

/**
 * Обработка очереди — вызывается кроном.
 *
 * @param int $limit Максимум задач за один запуск.
 * @return array Статистика [processed, ok, error]
 */
function fn_rlsv_autopost_process_queue($limit = 20)
{
    $limit = max(1, (int) $limit);
    $max_attempts = 3;

    $tasks = db_get_array(
        "SELECT * FROM ?:rlsv_autopost_queue
         WHERE status = 'pending' AND run_after <= ?i AND attempts < ?i
         ORDER BY run_after ASC, queue_id ASC
         LIMIT ?i",
        TIME, $max_attempts, $limit
    );

    $stat = array('processed' => 0, 'ok' => 0, 'error' => 0);

    fn_rlsv_autopost_log('cron.tick', array('data' => array('found' => count($tasks), 'limit' => $limit)));

    foreach ($tasks as $task) {
        $stat['processed']++;

        // Атомарный «захват» задачи: UPDATE сработает только если она ещё 'pending'.
        // Если параллельный крон уже взял её, affected rows = 0 — пропускаем без обработки.
        $claimed = db_query(
            "UPDATE ?:rlsv_autopost_queue SET status = 'processing', attempts = attempts + 1 WHERE queue_id = ?i AND status = 'pending'",
            $task['queue_id']
        );
        if (empty($claimed)) {
            $stat['processed']--; // задачу уже забрал другой процесс
            continue;
        }

        $tokens = fn_rlsv_autopost_get_user_tokens($task['user_id']);
        $card   = json_decode($task['payload'], true);

        $result = fn_rlsv_autopost_dispatch($task['platform'], $card, $tokens, $task);

        if (!empty($result['status'])) {
            db_query(
                "UPDATE ?:rlsv_autopost_queue SET status = 'done', error = '', processed_at = ?i WHERE queue_id = ?i",
                TIME, $task['queue_id']
            );
            // Instagram: запоминаем связь «медиа → товар». Позже (крон или открытие
            // страницы аналитики) метрики insights пишутся в привязанные характеристики.
            if ($task['platform'] === 'instagram' && !empty($result['external_id'])) {
                fn_rlsv_autopost_remember_ig_media(
                    $result['external_id'],
                    (int) $task['product_id'],
                    (int) $task['user_id'],
                    isset($result['media_type']) ? $result['media_type'] : 'FEED_IMAGE'
                );
            }
            $stat['ok']++;
        } else {
            $error  = isset($result['error']) ? $result['error'] : 'Unknown error';
            $attempt = $task['attempts'] + 1;
            // Если попытки исчерпаны — финальный статус error, иначе вернём в pending.
            $new_status = ($attempt >= $max_attempts) ? 'error' : 'pending';
            // Экспоненциальная пауза перед повтором (2,4,8… мин), чтобы не упираться в лимиты API.
            $backoff = ($new_status === 'pending') ? (int) (60 * pow(2, $attempt)) : 0;
            db_query(
                "UPDATE ?:rlsv_autopost_queue SET status = ?s, error = ?s, run_after = ?i WHERE queue_id = ?i",
                $new_status, $error, TIME + $backoff, $task['queue_id']
            );
            $stat['error']++;
        }
    }

    fn_rlsv_autopost_log('cron.done', array('data' => $stat));

    return $stat;
}

/**
 * Маршрутизация задачи к нужному отправителю.
 */
/**
 * Текущий прокси для исходящих запросов (на время публикации пользователя).
 * Геттер/сеттер через статическую переменную.
 */
function fn_rlsv_autopost_proxy($set = null)
{
    static $proxy = '';
    if ($set !== null) {
        $proxy = trim((string) $set);
    }

    return $proxy;
}

function fn_rlsv_autopost_dispatch($platform, $card, $tokens, $task)
{
    // Прокси пользователя (если указан в профиле) — применяется ко всем исходящим запросам.
    fn_rlsv_autopost_proxy(isset($tokens['proxy']) ? $tokens['proxy'] : '');

    switch ($platform) {
        case 'vk':        return fn_rlsv_autopost_send_vk($card, $tokens);
        case 'instagram': return fn_rlsv_autopost_send_instagram($card, $tokens);
        case 'telegram':  return fn_rlsv_autopost_send_telegram($card, $tokens);
        case 'ok':        return fn_rlsv_autopost_send_ok($card, $tokens);
        case 'pinterest': return fn_rlsv_autopost_send_pinterest($card, $tokens);
        case 'max':       return fn_rlsv_autopost_send_max($card, $tokens);
        case 'youtube':   return fn_rlsv_autopost_send_youtube($card, $tokens);
        case 'rutube':    return fn_rlsv_autopost_send_rutube($card, $tokens);
        default:
            return array('status' => false, 'error' => 'Unknown platform: ' . $platform);
    }
}

/* ======================================================================
 *  ФОРМИРОВАНИЕ КАРТОЧКИ
 * ====================================================================== */

/**
 * Собирает данные карточки коммерческой недвижимости из товара CS-Cart.
 * Эстетика: минимализм, «тихая роскошь» — короткий заголовок, чистый текст.
 */
function fn_rlsv_autopost_build_card($product_id, $lang_code = CART_LANGUAGE)
{
    $auth = array();
    $product = fn_get_product_data($product_id, $auth, $lang_code, '', true, true, true);
    if (empty($product)) {
        return array();
    }

    // Абсолютный URL главного изображения (нужен для большинства площадок).
    $image_url = '';
    if (!empty($product['main_pair']['detailed']['image_path'])) {
        $image_url = $product['main_pair']['detailed']['image_path'];
    } elseif (!empty($product['main_pair']['icon']['image_path'])) {
        $image_url = $product['main_pair']['icon']['image_path'];
    }

    // URL видеопрезентации — из заданной фичи товара (для YouTube/RuTube).
    $video_url = '';
    $video_feature_id = (int) Registry::get('addons.rlsv_autopost.video_object_feature_id');
    if ($video_feature_id && !empty($product['product_features'][$video_feature_id]['value'])) {
        $video_url = $product['product_features'][$video_feature_id]['value'];
    }
    // Переопределение из вкладки карточки товара (если задано) имеет приоритет.
    $product_settings = fn_rlsv_autopost_get_product_settings($product_id);
    if (!empty($product_settings['video_url'])) {
        $video_url = $product_settings['video_url'];
    }

    $price = !empty($product['price']) ? fn_format_price($product['price'], CART_PRIMARY_CURRENCY) : '';

    $url = fn_url('products.view?product_id=' . $product_id, 'C');

    // Минималистичный текст в стиле «тихой роскоши».
    $description = !empty($product['short_description'])
        ? $product['short_description']
        : (!empty($product['full_description']) ? $product['full_description'] : '');
    $description = trim(strip_tags($description));

    $title = $product['product'];
    $text  = $title;
    if ($price !== '') {
        $text .= "\n" . $price;
    }
    if ($description !== '') {
        $text .= "\n\n" . fn_truncate_chars($description, 600);
    }
    $text .= "\n\n" . $url;

    return array(
        'product_id'  => (int) $product_id,
        'title'       => $title,
        'text'        => $text,
        'description' => $description,
        'price'       => $price,
        'image_url'   => $image_url,
        'video_url'   => $video_url,
        'url'         => $url,
    );
}

/* ======================================================================
 *  ОТПРАВИТЕЛИ ПЛОЩАДОК (активный PUSH)
 *
 *  ВНИМАНИЕ: конечные точки, имена параметров и scope у внешних API
 *  периодически меняются. Структура запросов соответствует публичной
 *  документации соответствующих платформ; перед боевым запуском сверьте
 *  актуальные значения в их dev-консолях.
 * ====================================================================== */

/** Унифицированный HTTP-вызов с логированием. Через прокси (если задан) — cURL, иначе Tygh\Http. */
function fn_rlsv_autopost_http($method, $url, $data = array(), $extra = array(), $platform = '')
{
    $method = strtoupper($method);
    $proxy  = fn_rlsv_autopost_proxy();

    if ($proxy !== '') {
        // cURL-путь через прокси.
        $headers = isset($extra['headers']) ? $extra['headers'] : array();
        if ($method === 'GET') {
            if (!empty($data)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
            }
            $body = '';
        } else {
            $body = $data; // массив (form/multipart) или строка (raw/JSON)
        }
        $r = fn_rlsv_autopost_curl_raw($method, $url, $body, $headers);
        $status   = $r['http_status'];
        $response = $r['body'];
    } else {
        $response = ($method === 'GET') ? Http::get($url, $data, $extra) : Http::post($url, $data, $extra);
        $status   = method_exists('Tygh\\Http', 'getStatus') ? Http::getStatus() : 0;
    }

    fn_rlsv_autopost_log('api.' . $platform, array(
        'request'  => array('method' => $method, 'url' => $url, 'data' => $data, 'proxy' => $proxy !== '' ? 'yes' : 'no'),
        'response' => array('http_status' => $status, 'body' => mb_substr((string) $response, 0, 4000)),
    ));

    return array('http_status' => $status, 'body' => $response);
}

/** ВКонтакте: wall.post с прикреплённым фото (photos.getWallUploadServer -> upload -> save). */
function fn_rlsv_autopost_send_vk($card, $tokens)
{
    $api    = 'https://api.vk.com/method/';
    $v      = '5.131';
    $token  = $tokens['vk_access_token'];
    $owner  = (int) $tokens['vk_owner_id']; // для сообщества — со знаком минус
    $group  = $owner < 0 ? abs($owner) : 0;

    $attachment = '';
    if (!empty($card['image_url'])) {
        // 1) сервер загрузки
        $r = fn_rlsv_autopost_http('GET', $api . 'photos.getWallUploadServer',
            array('access_token' => $token, 'v' => $v, 'group_id' => $group), array(), 'vk');
        $up = json_decode($r['body'], true);
        $upload_url = isset($up['response']['upload_url']) ? $up['response']['upload_url'] : '';

        if ($upload_url) {
            // 2) выгрузка файла на upload_url (multipart)
            $tmp = fn_rlsv_autopost_download_to_tmp($card['image_url']);
            if ($tmp) {
                $r2 = fn_rlsv_autopost_http('POST', $upload_url,
                    array('photo' => fn_rlsv_autopost_curl_file($tmp)), array(), 'vk');
                @unlink($tmp);
                $u = json_decode($r2['body'], true);

                if (!empty($u['photo'])) {
                    // 3) сохранение фото
                    $r3 = fn_rlsv_autopost_http('POST', $api . 'photos.saveWallPhoto', array(
                        'access_token' => $token, 'v' => $v, 'group_id' => $group,
                        'photo' => $u['photo'], 'server' => $u['server'], 'hash' => $u['hash'],
                    ), array(), 'vk');
                    $s = json_decode($r3['body'], true);
                    if (!empty($s['response'][0])) {
                        $p = $s['response'][0];
                        $attachment = 'photo' . $p['owner_id'] . '_' . $p['id'];
                    }
                }
            }
        }
    }

    $r = fn_rlsv_autopost_http('POST', $api . 'wall.post', array(
        'access_token' => $token,
        'v'            => $v,
        'owner_id'     => $owner,
        'from_group'   => 1,
        'message'      => $card['text'],
        'attachments'  => $attachment,
    ), array(), 'vk');

    $res = json_decode($r['body'], true);
    if (!empty($res['response']['post_id'])) {
        return array('status' => true);
    }

    return array('status' => false, 'error' => 'VK: ' . $r['body']);
}

/** Instagram Graph API: создаём media-container, затем публикуем. Нужен публичный image_url. */
function fn_rlsv_autopost_send_instagram($card, $tokens)
{
    if (empty($card['image_url'])) {
        return array('status' => false, 'error' => 'Instagram требует публичный image_url');
    }

    $base  = 'https://graph.facebook.com/v19.0/';
    $ig_id = $tokens['ig_user_id'];
    $token = $tokens['ig_access_token'];

    // 1) контейнер
    $r = fn_rlsv_autopost_http('POST', $base . $ig_id . '/media', array(
        'image_url'    => $card['image_url'],
        'caption'      => $card['text'],
        'access_token' => $token,
    ), array(), 'instagram');
    $c = json_decode($r['body'], true);
    if (empty($c['id'])) {
        return array('status' => false, 'error' => 'Instagram container: ' . $r['body']);
    }

    // 2) публикация
    $r2 = fn_rlsv_autopost_http('POST', $base . $ig_id . '/media_publish', array(
        'creation_id'  => $c['id'],
        'access_token' => $token,
    ), array(), 'instagram');
    $p = json_decode($r2['body'], true);

    return !empty($p['id'])
        ? array('status' => true, 'external_id' => $p['id'], 'media_type' => 'FEED_IMAGE')
        : array('status' => false, 'error' => 'Instagram publish: ' . $r2['body']);
}

/** Telegram Bot API: sendPhoto принимает URL фото напрямую (без multipart). */
function fn_rlsv_autopost_send_telegram($card, $tokens)
{
    $bot  = $tokens['tg_bot_token'];
    $chat = $tokens['tg_chat_id'];

    if (!empty($card['image_url'])) {
        $r = fn_rlsv_autopost_http('POST', "https://api.telegram.org/bot{$bot}/sendPhoto", array(
            'chat_id' => $chat,
            'photo'   => $card['image_url'],
            'caption' => mb_substr($card['text'], 0, 1024),
        ), array(), 'telegram');
    } else {
        $r = fn_rlsv_autopost_http('POST', "https://api.telegram.org/bot{$bot}/sendMessage", array(
            'chat_id' => $chat,
            'text'    => $card['text'],
        ), array(), 'telegram');
    }

    $res = json_decode($r['body'], true);

    return (!empty($res['ok']))
        ? array('status' => true)
        : array('status' => false, 'error' => 'Telegram: ' . $r['body']);
}

/** Одноклассники: mediatopic.post. Запросы подписываются MD5 (sig). */
function fn_rlsv_autopost_send_ok($card, $tokens)
{
    $access  = $tokens['ok_access_token'];
    $app_key = $tokens['ok_application_key'];
    $secret  = $tokens['ok_session_secret_key']; // секрет сессии

    $attachment = array(
        'media' => array(
            array('type' => 'text', 'text' => $card['text']),
        ),
    );
    if (!empty($card['image_url'])) {
        $attachment['media'][] = array('type' => 'photo', 'list' => array(array('existing_photo_id' => '')));
        // Примечание: для фото OK требует предварительной загрузки через photosV2.* —
        // здесь публикуем текстовый media-topic; загрузку фото добавьте при необходимости.
        array_pop($attachment['media']);
    }

    $params = array(
        'application_key' => $app_key,
        'method'          => 'mediatopic.post',
        'type'            => 'GROUP_THEME',
        'attachment'      => json_encode($attachment, JSON_UNESCAPED_UNICODE),
        'format'          => 'json',
    );

    // Подпись OK: md5( concat(sorted "k=v") + md5(access_token + secret) )
    ksort($params);
    $base = '';
    foreach ($params as $k => $val) {
        $base .= $k . '=' . $val;
    }
    $sig = md5($base . md5($access . $secret));

    $params['sig']          = $sig;
    $params['access_token'] = $access;

    $r = fn_rlsv_autopost_http('POST', 'https://api.ok.ru/fb.do', $params, array(), 'ok');
    $res = json_decode($r['body'], true);

    // mediatopic.post возвращает id топика строкой.
    return (is_string($res) || !empty($res['id']) || (!empty($res) && empty($res['error_code'])))
        ? array('status' => true)
        : array('status' => false, 'error' => 'OK: ' . $r['body']);
}

/** Pinterest API v5: создание пина (JSON, Bearer-токен). */
function fn_rlsv_autopost_send_pinterest($card, $tokens)
{
    if (empty($card['image_url'])) {
        return array('status' => false, 'error' => 'Pinterest требует image_url');
    }

    $body = json_encode(array(
        'board_id'    => $tokens['pin_board_id'],
        'title'       => mb_substr($card['title'], 0, 100),
        'description' => mb_substr($card['text'], 0, 800),
        'link'        => $card['url'],
        'media_source' => array(
            'source_type' => 'image_url',
            'url'         => $card['image_url'],
        ),
    ), JSON_UNESCAPED_UNICODE);

    $extra = array('headers' => array(
        'Authorization: Bearer ' . $tokens['pin_access_token'],
        'Content-Type: application/json',
    ));

    $r = fn_rlsv_autopost_http('POST', 'https://api.pinterest.com/v5/pins', $body, $extra, 'pinterest');
    $res = json_decode($r['body'], true);

    return (!empty($res['id']))
        ? array('status' => true)
        : array('status' => false, 'error' => 'Pinterest: ' . $r['body']);
}

/** Канал в МАКС: публикация через входящий webhook (JSON). */
function fn_rlsv_autopost_send_max($card, $tokens)
{
    $url  = $tokens['max_webhook_url'];
    $body = json_encode(array(
        'text'      => $card['text'],
        'image_url' => $card['image_url'],
        'link'      => $card['url'],
    ), JSON_UNESCAPED_UNICODE);

    $extra_headers = array('Content-Type: application/json');
    if (!empty($tokens['max_token'])) {
        $extra_headers[] = 'Authorization: Bearer ' . $tokens['max_token'];
    }

    $r = fn_rlsv_autopost_curl_raw('POST', $url, $body, $extra_headers);
    fn_rlsv_autopost_log('api.max', array(
        'request'  => array('url' => $url, 'data' => $body),
        'response' => array('http_status' => $r['http_status'], 'body' => mb_substr($r['body'], 0, 2000)),
    ));

    return ($r['http_status'] >= 200 && $r['http_status'] < 300)
        ? array('status' => true)
        : array('status' => false, 'error' => 'MAX: HTTP ' . $r['http_status'] . ' ' . $r['body']);
}

/** YouTube Data API v3: обновляем access_token по refresh_token и грузим видео (resumable). */
function fn_rlsv_autopost_send_youtube($card, $tokens)
{
    if (empty($card['video_url'])) {
        return array('status' => false, 'error' => 'YouTube: не задан URL видеопрезентации');
    }

    // 1) свежий access_token
    $tr = fn_rlsv_autopost_http('POST', 'https://oauth2.googleapis.com/token', array(
        'client_id'     => $tokens['yt_client_id'],
        'client_secret' => $tokens['yt_client_secret'],
        'refresh_token' => $tokens['yt_refresh_token'],
        'grant_type'    => 'refresh_token',
    ), array(), 'youtube');
    $tok = json_decode($tr['body'], true);
    if (empty($tok['access_token'])) {
        return array('status' => false, 'error' => 'YouTube token: ' . $tr['body']);
    }
    $access = $tok['access_token'];

    // 2) скачиваем видео во временный файл
    $tmp = fn_rlsv_autopost_download_to_tmp($card['video_url']);
    if (!$tmp) {
        return array('status' => false, 'error' => 'YouTube: не удалось скачать видео');
    }
    $filesize = filesize($tmp);

    $meta = json_encode(array(
        'snippet' => array(
            'title'       => mb_substr($card['title'], 0, 100),
            'description' => mb_substr($card['text'], 0, 5000),
            'categoryId'  => '22',
        ),
        'status' => array('privacyStatus' => 'public'),
    ), JSON_UNESCAPED_UNICODE);

    // 3) инициируем resumable-сессию (отдаёт Location)
    $init = fn_rlsv_autopost_curl_raw(
        'POST',
        'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status',
        $meta,
        array(
            'Authorization: Bearer ' . $access,
            'Content-Type: application/json; charset=UTF-8',
            'X-Upload-Content-Type: video/*',
            'X-Upload-Content-Length: ' . $filesize,
        )
    );
    $location = '';
    if (preg_match('/location:\s*(\S+)/i', $init['headers'], $m)) {
        $location = trim($m[1]);
    }
    if (!$location) {
        @unlink($tmp);
        return array('status' => false, 'error' => 'YouTube resumable init: ' . $init['headers']);
    }

    // 4) загружаем тело файла
    $put = fn_rlsv_autopost_curl_raw('PUT', $location, '@' . $tmp,
        array('Content-Type: video/*', 'Content-Length: ' . $filesize), $tmp);
    @unlink($tmp);

    fn_rlsv_autopost_log('api.youtube', array('response' => array(
        'http_status' => $put['http_status'], 'body' => mb_substr($put['body'], 0, 2000),
    )));
    $res = json_decode($put['body'], true);

    return (!empty($res['id']))
        ? array('status' => true)
        : array('status' => false, 'error' => 'YouTube upload: ' . $put['body']);
}

/** RuTube Partner API: загрузка видео по ссылке-источнику. */
function fn_rlsv_autopost_send_rutube($card, $tokens)
{
    if (empty($card['video_url'])) {
        return array('status' => false, 'error' => 'RuTube: не задан URL видео');
    }

    $extra = array('headers' => array(
        'Authorization: Bearer ' . $tokens['rt_access_token'],
        'Content-Type: application/json',
    ));

    $body = json_encode(array(
        'url'         => $card['video_url'],
        'title'       => mb_substr($card['title'], 0, 100),
        'description' => mb_substr($card['text'], 0, 1000),
        'category'    => isset($tokens['rt_category_id']) ? (int) $tokens['rt_category_id'] : 13,
    ), JSON_UNESCAPED_UNICODE);

    $r = fn_rlsv_autopost_http('POST', 'https://rutube.ru/api/video/', $body, $extra, 'rutube');
    $res = json_decode($r['body'], true);

    return (!empty($res['video_id']) || !empty($res['id']))
        ? array('status' => true)
        : array('status' => false, 'error' => 'RuTube: ' . $r['body']);
}

/* ======================================================================
 *  ВСПОМОГАТЕЛЬНОЕ: скачивание файла и низкоуровневый cURL
 *  (нужно там, где Tygh\Http недостаточно — multipart и resumable upload)
 * ====================================================================== */

function fn_rlsv_autopost_download_to_tmp($url)
{
    $tmp   = fn_create_temp_file();
    $proxy = fn_rlsv_autopost_proxy();

    if ($proxy !== '') {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        $data = curl_exec($ch);
        curl_close($ch);
    } else {
        $data = Http::get($url);
    }

    if ($data === false || $data === '' || $data === null) {
        return false;
    }
    fn_put_contents($tmp, $data);

    return $tmp;
}

function fn_rlsv_autopost_curl_file($path)
{
    if (class_exists('CURLFile')) {
        return new CURLFile($path);
    }

    return '@' . $path;
}

/**
 * Низкоуровневый cURL для случаев, недоступных через Tygh\Http
 * (получение заголовков ответа, PUT тела файла).
 */
function fn_rlsv_autopost_curl_raw($method, $url, $body = '', $headers = array(), $put_file = '')
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);

    $proxy = fn_rlsv_autopost_proxy();
    if ($proxy !== '') {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
    }

    if (strtoupper($method) === 'PUT' && $put_file && is_file($put_file)) {
        $fp = fopen($put_file, 'rb');
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($put_file));
    } elseif (is_array($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body); // form / multipart
    } elseif (is_string($body) && $body !== '' && $body[0] !== '@') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body); // raw / JSON
    }

    $raw    = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    if (isset($fp) && is_resource($fp)) {
        fclose($fp);
    }

    return array(
        'http_status' => $status,
        'headers'     => substr((string) $raw, 0, $hsize),
        'body'        => substr((string) $raw, $hsize),
    );
}

/* ======================================================================
 *  ПАССИВНЫЙ PULL: XML-ФИДЫ (Дзен / Циан / Домклик)
 * ====================================================================== */

/**
 * Возвращает товары целевой категории для построения фида.
 */
function fn_rlsv_autopost_get_feed_products($lang_code = CART_LANGUAGE)
{
    $target = (int) Registry::get('addons.rlsv_autopost.target_category_id');

    $cond = '';
    if ($target) {
        $cond = db_quote(
            " AND products.product_id IN (SELECT product_id FROM ?:products_categories WHERE category_id = ?i)",
            $target
        );
    }

    $ids = db_get_fields(
        "SELECT products.product_id FROM ?:products AS products
         WHERE products.status = 'A' ?p",
        $cond
    );

    $items = array();
    foreach ($ids as $pid) {
        $items[] = fn_rlsv_autopost_build_card($pid, $lang_code);
    }

    return $items;
}

/**
 * Строит XML фида под нужный формат.
 *
 * @param string $format dzen|cian|domclick
 */
function fn_rlsv_autopost_build_feed($format, $lang_code = CART_LANGUAGE)
{
    $items = fn_rlsv_autopost_get_feed_products($lang_code);

    fn_rlsv_autopost_log('feed.build', array('data' => array('format' => $format, 'items' => count($items))));

    $generation_date = date('Y-m-d\TH:i:sP');

    switch ($format) {
        case 'cian':
        case 'domclick':
            // Формат, близкий к Cian/Domclick (feed_version + объекты).
            $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<feed>' . "\n  <feed_version>2</feed_version>\n";
            foreach ($items as $it) {
                $xml .= "  <object>\n";
                $xml .= '    <id>' . (int) $it['product_id'] . "</id>\n";
                $xml .= '    <title>' . fn_rlsv_autopost_xml($it['title']) . "</title>\n";
                $xml .= '    <description>' . fn_rlsv_autopost_xml($it['description']) . "</description>\n";
                $xml .= '    <price>' . fn_rlsv_autopost_xml(strip_tags((string) $it['price'])) . "</price>\n";
                if (!empty($it['image_url'])) {
                    $xml .= '    <photo>' . fn_rlsv_autopost_xml($it['image_url']) . "</photo>\n";
                }
                $xml .= '    <url>' . fn_rlsv_autopost_xml($it['url']) . "</url>\n";
                $xml .= "  </object>\n";
            }
            $xml .= '</feed>';

            return $xml;

        case 'dzen':
        default:
            // Дзен — RSS 2.0 с медиа-вложениями.
            $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" '
                  . 'xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n";
            $xml .= "<channel>\n";
            $xml .= '  <title>' . fn_rlsv_autopost_xml(Registry::get('settings.Company.company_name')) . "</title>\n";
            $xml .= '  <link>' . fn_rlsv_autopost_xml(fn_url('', 'C')) . "</link>\n";
            $xml .= "  <description>RLSV — коммерческая недвижимость</description>\n";
            $xml .= '  <lastBuildDate>' . $generation_date . "</lastBuildDate>\n";
            foreach ($items as $it) {
                $xml .= "  <item>\n";
                $xml .= '    <title>' . fn_rlsv_autopost_xml($it['title']) . "</title>\n";
                $xml .= '    <link>' . fn_rlsv_autopost_xml($it['url']) . "</link>\n";
                $xml .= '    <guid>' . fn_rlsv_autopost_xml($it['url']) . "</guid>\n";
                $xml .= '    <pubDate>' . $generation_date . "</pubDate>\n";
                $xml .= '    <description><![CDATA[' . $it['description'] . ']]></description>' . "\n";
                if (!empty($it['image_url'])) {
                    $xml .= '    <enclosure url="' . fn_rlsv_autopost_xml($it['image_url']) . '" type="image/jpeg"/>' . "\n";
                }
                $xml .= "  </item>\n";
            }
            $xml .= "</channel>\n</rss>";

            return $xml;
    }
}

function fn_rlsv_autopost_xml($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/* ======================================================================
 *  СТАТИСТИКА ПУБЛИКАЦИЙ ПОЛЬЗОВАТЕЛЯ (для страницы статистики)
 * ====================================================================== */

/**
 * Сводка и журнал публикаций администратора из очереди.
 */
function fn_rlsv_autopost_get_user_stats($user_id, $limit = 200)
{
    $user_id = (int) $user_id;

    $by_status = db_get_hash_single_array(
        "SELECT status, COUNT(*) AS cnt FROM ?:rlsv_autopost_queue WHERE user_id = ?i GROUP BY status",
        array('status', 'cnt'), $user_id
    );

    $by_platform = db_get_hash_single_array(
        "SELECT platform, COUNT(*) AS cnt FROM ?:rlsv_autopost_queue
         WHERE user_id = ?i AND status = 'done' GROUP BY platform",
        array('platform', 'cnt'), $user_id
    );

    $rows = db_get_array(
        "SELECT * FROM ?:rlsv_autopost_queue WHERE user_id = ?i ORDER BY queue_id DESC LIMIT ?i",
        $user_id, (int) $limit
    );

    $pids = array();
    foreach ($rows as $r) {
        $pids[(int) $r['product_id']] = (int) $r['product_id'];
    }
    $names = array();
    if (!empty($pids)) {
        $names = db_get_hash_single_array(
            "SELECT product_id, product FROM ?:product_descriptions
             WHERE product_id IN (?n) AND lang_code = ?s",
            array('product_id', 'product'), $pids, CART_LANGUAGE
        );
    }
    foreach ($rows as $k => $r) {
        $rows[$k]['product_name'] = isset($names[$r['product_id']]) ? $names[$r['product_id']] : ('#' . $r['product_id']);
    }

    return array(
        'total'       => array_sum($by_status),
        'done'        => isset($by_status['done']) ? (int) $by_status['done'] : 0,
        'error'       => isset($by_status['error']) ? (int) $by_status['error'] : 0,
        'pending'     => (isset($by_status['pending']) ? (int) $by_status['pending'] : 0)
                       + (isset($by_status['processing']) ? (int) $by_status['processing'] : 0),
        'by_platform' => $by_platform,
        'rows'        => $rows,
    );
}

/**
 * Обзор по всем пользователям (для страницы статистики без user_id).
 */
function fn_rlsv_autopost_get_publishers_overview()
{
    $rows = db_get_array(
        "SELECT q.user_id,
                COUNT(*) AS total,
                SUM(q.status = 'done')  AS done,
                SUM(q.status = 'error') AS err,
                CONCAT(u.firstname, ' ', u.lastname) AS name,
                u.email AS email
         FROM ?:rlsv_autopost_queue AS q
         LEFT JOIN ?:users AS u ON u.user_id = q.user_id
         GROUP BY q.user_id
         ORDER BY total DESC"
    );

    return $rows;
}

/**
 * Очистка от мусора (для администратора).
 *
 * @param string $what  done|errors|old|orphans|all_queue
 * @param int    $days  для $what='old' — старше скольких дней
 */
function fn_rlsv_autopost_cleanup($what, $days = 30)
{
    switch ($what) {
        case 'done':
            db_query("DELETE FROM ?:rlsv_autopost_queue WHERE status = 'done'");
            break;

        case 'errors':
            db_query("DELETE FROM ?:rlsv_autopost_queue WHERE status = 'error'");
            break;

        case 'old':
            $ts = TIME - (max(1, (int) $days) * 86400);
            db_query("DELETE FROM ?:rlsv_autopost_queue WHERE status IN ('done','error') AND created_at < ?i", $ts);
            break;

        case 'all_queue':
            db_query("TRUNCATE TABLE ?:rlsv_autopost_queue");
            break;

        case 'orphans':
            // Битые привязки и осиротевшие записи (удалённые товары/категории/пользователи).
            db_query("DELETE l FROM ?:rlsv_autopost_user_links AS l
                      LEFT JOIN ?:products AS p ON p.product_id = l.object_id
                      WHERE l.link_type = 'product' AND p.product_id IS NULL");
            db_query("DELETE l FROM ?:rlsv_autopost_user_links AS l
                      LEFT JOIN ?:categories AS c ON c.category_id = l.object_id
                      WHERE l.link_type = 'category' AND c.category_id IS NULL");
            db_query("DELETE l FROM ?:rlsv_autopost_user_links AS l
                      LEFT JOIN ?:users AS u ON u.user_id = l.user_id
                      WHERE u.user_id IS NULL");
            db_query("DELETE t FROM ?:rlsv_autopost_tokens AS t
                      LEFT JOIN ?:users AS u ON u.user_id = t.user_id
                      WHERE u.user_id IS NULL");
            db_query("DELETE pp FROM ?:rlsv_autopost_products AS pp
                      LEFT JOIN ?:products AS p ON p.product_id = pp.product_id
                      WHERE p.product_id IS NULL");
            db_query("DELETE q FROM ?:rlsv_autopost_queue AS q
                      LEFT JOIN ?:products AS p ON p.product_id = q.product_id
                      WHERE p.product_id IS NULL");
            break;

        default:
            return false;
    }

    fn_rlsv_autopost_log('cleanup', array('data' => array('what' => $what, 'days' => $days)));

    return true;
}

/* ======================================================================
 *  ДОБАВЛЕНО: БЕЗОПАСНОСТЬ И КЛИЕНТСКАЯ ЧАСТЬ (ФРОНТЕНД)
 *  - CSRF-токен для форм витрины
 *  - Санитайзер HTML для «подробного описания»
 *  - Проверка прав пользователя на товар
 *  - Валидация прокси
 *  - Витринная аналитика (по каналам и по товарам)
 * ====================================================================== */

/**
 * Белый список ключей токенов, которые разрешено принимать с витрины.
 * Любые другие поля из запроса игнорируются.
 */
function fn_rlsv_autopost_token_field_whitelist()
{
    return array(
        'proxy',
        'vk_access_token', 'vk_owner_id',
        'ig_access_token', 'ig_user_id',
        'tg_bot_token', 'tg_chat_id',
        'ok_access_token', 'ok_application_key', 'ok_session_secret_key',
        'pin_access_token', 'pin_board_id',
        'max_webhook_url', 'max_token',
        'yt_client_id', 'yt_client_secret', 'yt_refresh_token',
        'rt_access_token', 'rt_category_id',
    );
}

/**
 * Возвращает (и при первом обращении генерирует) CSRF-токен сессии.
 */
function fn_rlsv_autopost_csrf_token()
{
    if (empty($_SESSION['rlsv_autopost_csrf'])) {
        $_SESSION['rlsv_autopost_csrf'] = function_exists('random_bytes')
            ? bin2hex(random_bytes(16))
            : md5(uniqid((string) TIME, true));
    }

    return $_SESSION['rlsv_autopost_csrf'];
}

/**
 * Сверяет CSRF-токен запроса с сессионным (constant-time).
 */
function fn_rlsv_autopost_verify_csrf()
{
    $sent = isset($_REQUEST['rlsv_csrf']) ? (string) $_REQUEST['rlsv_csrf'] : '';
    $real = isset($_SESSION['rlsv_autopost_csrf']) ? (string) $_SESSION['rlsv_autopost_csrf'] : '';

    return $sent !== '' && $real !== '' && hash_equals($real, $sent);
}

/**
 * Санитайзер HTML для пользовательского «подробного описания».
 * Allowlist тегов/атрибутов на DOMDocument; вырезает script/style/iframe/обработчики
 * событий и javascript:/data: URI. Для промышленного использования рекомендуется
 * HTMLPurifier, но эта реализация не имеет внешних зависимостей.
 */
function fn_rlsv_autopost_sanitize_html($html)
{
    $html = (string) $html;
    if (trim($html) === '') {
        return '';
    }
    if (fn_strlen($html) > 50000) {
        $html = fn_substr($html, 0, 50000);
    }

    $allowed = array(
        'p' => array(), 'br' => array(), 'b' => array(), 'strong' => array(),
        'i' => array(), 'em' => array(), 'u' => array(), 'ul' => array(),
        'ol' => array(), 'li' => array(), 'blockquote' => array(),
        'h2' => array(), 'h3' => array(), 'h4' => array(), 'span' => array(),
        'a' => array('href', 'title', 'target', 'rel'),
        'img' => array('src', 'alt', 'title', 'width', 'height'),
        'table' => array(), 'thead' => array(), 'tbody' => array(),
        'tr' => array(), 'td' => array(), 'th' => array(),
    );
    $drop_with_content = array('script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'svg', 'link', 'meta', 'base');

    if (!class_exists('DOMDocument')) {
        // Аварийный путь без DOM: оставить только базовые теги (атрибуты при этом не вырезаются!).
        return strip_tags($html, '<p><br><b><strong><i><em><u><ul><ol><li><a><h2><h3><h4><blockquote>');
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $wrapped = '<?xml encoding="UTF-8"><div id="rlsv-root">' . $html . '</div>';
    $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    if (!$loaded) {
        return strip_tags($html, '<p><br><b><strong><i><em><u><ul><ol><li><a><h2><h3><h4><blockquote>');
    }

    $xpath = new DOMXPath($dom);
    $nodes = iterator_to_array($xpath->query('//*'));

    foreach ($nodes as $node) {
        $tag = strtolower($node->nodeName);

        if (!isset($allowed[$tag])) {
            if (in_array($tag, $drop_with_content, true)) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            } elseif ($node->parentNode) {
                // «Разворачиваем» неизвестный тег: оставляем его текст/детей.
                while ($node->firstChild) {
                    $node->parentNode->insertBefore($node->firstChild, $node);
                }
                $node->parentNode->removeChild($node);
            }
            continue;
        }

        if ($node->hasAttributes()) {
            foreach (iterator_to_array($node->attributes) as $attr) {
                $an = strtolower($attr->nodeName);
                if (!in_array($an, $allowed[$tag], true)) {
                    $node->removeAttribute($attr->nodeName);
                    continue;
                }
                if (in_array($an, array('href', 'src'), true)
                    && preg_match('/^\s*(javascript|data|vbscript):/i', (string) $attr->nodeValue)
                ) {
                    $node->removeAttribute($attr->nodeName);
                }
            }
        }

        if ($tag === 'a') {
            $node->setAttribute('rel', 'nofollow noopener noreferrer');
        }
    }

    $root = $dom->getElementById('rlsv-root');
    if (!$root) {
        $divs = $dom->getElementsByTagName('div');
        $root = $divs->length ? $divs->item(0) : null;
    }

    $out = '';
    if ($root) {
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
    }

    return trim($out);
}

/**
 * Может ли пользователь витрины редактировать данный товар.
 * Право выдаётся ТОЛЬКО администратором через привязки (rlsv_autopost_user_links):
 * либо прямая привязка товара, либо через привязанную категорию товара.
 * Самопривязка с витрины невозможна — это защищает от эскалации прав.
 */
function fn_rlsv_autopost_user_can_edit_product($user_id, $product_id)
{
    $user_id    = (int) $user_id;
    $product_id = (int) $product_id;
    if (!$user_id || !$product_id) {
        return false;
    }

    $direct = db_get_field(
        "SELECT 1 FROM ?:rlsv_autopost_user_links
         WHERE user_id = ?i AND link_type = 'product' AND object_id = ?i",
        $user_id, $product_id
    );
    if ($direct) {
        return true;
    }

    $via_category = db_get_field(
        "SELECT 1 FROM ?:rlsv_autopost_user_links AS l
         INNER JOIN ?:products_categories AS pc
                 ON pc.category_id = l.object_id AND pc.product_id = ?i
         WHERE l.user_id = ?i AND l.link_type = 'category' LIMIT 1",
        $product_id, $user_id
    );

    return (bool) $via_category;
}

/**
 * Валидация строки прокси. Возвращает нормализованную строку,
 * '' (прокси не задан) или false (некорректное значение).
 */
function fn_rlsv_autopost_validate_proxy($proxy)
{
    $proxy = trim((string) $proxy);
    if ($proxy === '') {
        return '';
    }
    if (!preg_match('~^(https?|socks5h?|socks4)://~i', $proxy)) {
        $proxy = 'http://' . $proxy; // допускаем host:port
    }
    $parts = @parse_url($proxy);
    if (empty($parts['host']) || empty($parts['port'])) {
        return false;
    }

    return $proxy;
}

/**
 * Витринная аналитика по каналам: для каждой включённой площадки —
 * счётчики статусов и последние записи журнала пользователя.
 */
function fn_rlsv_autopost_get_user_stats_by_channel($user_id, $limit_per = 100)
{
    $user_id   = (int) $user_id;
    $platforms = fn_rlsv_autopost_get_enabled_platforms();
    $result    = array();

    foreach ($platforms as $p) {
        $counts = db_get_hash_single_array(
            "SELECT status, COUNT(*) AS cnt FROM ?:rlsv_autopost_queue
             WHERE user_id = ?i AND platform = ?s GROUP BY status",
            array('status', 'cnt'), $user_id, $p
        );

        $rows = db_get_array(
            "SELECT q.*, pd.product AS product_name
             FROM ?:rlsv_autopost_queue AS q
             LEFT JOIN ?:product_descriptions AS pd
                    ON pd.product_id = q.product_id AND pd.lang_code = ?s
             WHERE q.user_id = ?i AND q.platform = ?s
             ORDER BY q.queue_id DESC
             LIMIT ?i",
            CART_LANGUAGE, $user_id, $p, (int) $limit_per
        );

        $result[$p] = array(
            'total'   => array_sum($counts),
            'done'    => isset($counts['done']) ? (int) $counts['done'] : 0,
            'error'   => isset($counts['error']) ? (int) $counts['error'] : 0,
            'pending' => (isset($counts['pending']) ? (int) $counts['pending'] : 0)
                       + (isset($counts['processing']) ? (int) $counts['processing'] : 0),
            'rows'    => $rows,
        );
    }

    return $result;
}

/**
 * Витринная аналитика по товарам пользователя: агрегаты + разбивка по площадкам.
 */
function fn_rlsv_autopost_get_user_product_stats($user_id)
{
    $user_id = (int) $user_id;

    $rows = db_get_array(
        "SELECT q.product_id,
                COUNT(*) AS total,
                SUM(q.status = 'done')  AS done,
                SUM(q.status = 'error') AS err,
                SUM(q.status IN ('pending','processing')) AS pending,
                MAX(q.processed_at) AS last_at,
                pd.product AS product_name
         FROM ?:rlsv_autopost_queue AS q
         LEFT JOIN ?:product_descriptions AS pd
                ON pd.product_id = q.product_id AND pd.lang_code = ?s
         WHERE q.user_id = ?i
         GROUP BY q.product_id
         ORDER BY total DESC",
        CART_LANGUAGE, $user_id
    );

    $platform_rows = db_get_array(
        "SELECT product_id, platform, COUNT(*) AS cnt, SUM(status = 'done') AS done
         FROM ?:rlsv_autopost_queue WHERE user_id = ?i
         GROUP BY product_id, platform",
        $user_id
    );

    $by_product = array();
    foreach ($platform_rows as $pr) {
        $by_product[(int) $pr['product_id']][$pr['platform']] = array(
            'cnt'  => (int) $pr['cnt'],
            'done' => (int) $pr['done'],
        );
    }

    foreach ($rows as $k => $r) {
        $pid = (int) $r['product_id'];
        $rows[$k]['platforms']    = isset($by_product[$pid]) ? $by_product[$pid] : array();
        $rows[$k]['url']          = fn_url('products.view?product_id=' . $pid, 'C');
        $rows[$k]['edit_url']     = fn_url('rlsv_account.product?product_id=' . $pid, 'C');
        $rows[$k]['product_name'] = ($r['product_name'] !== null && $r['product_name'] !== '')
            ? $r['product_name'] : ('#' . $pid);
    }

    return $rows;
}

/**
 * Сохраняет «подробное описание» товара (full_description) с санитайзом.
 * Возвращает очищенный HTML.
 */
function fn_rlsv_autopost_save_product_full_description($product_id, $lang_code, $html)
{
    $product_id = (int) $product_id;
    $clean      = fn_rlsv_autopost_sanitize_html($html);

    db_query(
        "UPDATE ?:product_descriptions SET full_description = ?s WHERE product_id = ?i AND lang_code = ?s",
        $clean, $product_id, $lang_code
    );

    // Сбрасываем кэш витрины, если функция доступна в данной сборке ядра.
    if (function_exists('fn_clean_cache')) {
        fn_clean_cache();
    }

    fn_rlsv_autopost_log('product.full_description.save', array('data' => array(
        'product_id' => $product_id,
        'lang'       => $lang_code,
        'length'     => fn_strlen($clean),
    )));

    return $clean;
}

/* ======================================================================
 *  ДОБАВЛЕНО: ПРИВЯЗКА ИЗДАТЕЛЕЙ К ТОВАРУ СО СТОРОНЫ КАРТОЧКИ ТОВАРА
 *  (обратная сторона rlsv_autopost_user_links: выбираем пользователей
 *   для конкретного товара прямо в карточке товара).
 * ====================================================================== */

/**
 * Возвращает список user_id, привязанных к товару напрямую (link_type='product').
 *
 * @param  int   $product_id
 * @return int[]
 */
function fn_rlsv_autopost_get_product_publishers($product_id)
{
    $ids = db_get_fields(
        "SELECT user_id FROM ?:rlsv_autopost_user_links
         WHERE link_type = 'product' AND object_id = ?i",
        (int) $product_id
    );

    return array_map('intval', (array) $ids);
}

/**
 * Полностью переписывает набор издателей для товара (прямые привязки).
 * $user_ids может прийти массивом или строкой "1,2,3".
 *
 * @param int          $product_id
 * @param array|string $user_ids
 */
function fn_rlsv_autopost_save_product_publishers($product_id, $user_ids)
{
    $product_id = (int) $product_id;
    if (!$product_id) {
        return;
    }

    if (!is_array($user_ids)) {
        $user_ids = strlen((string) $user_ids) ? explode(',', (string) $user_ids) : array();
    }
    $user_ids = array_filter(array_unique(array_map('intval', $user_ids)));

    // Переписываем только прямые (product) привязки этого товара; категорийные не трогаем.
    db_query(
        "DELETE FROM ?:rlsv_autopost_user_links WHERE link_type = 'product' AND object_id = ?i",
        $product_id
    );
    foreach ($user_ids as $uid) {
        db_query("INSERT INTO ?:rlsv_autopost_user_links ?e", array(
            'user_id'   => $uid,
            'link_type' => 'product',
            'object_id' => $product_id,
        ));
    }

    fn_rlsv_autopost_log('product.publishers.save', array('data' => array(
        'product_id' => $product_id,
        'publishers' => count($user_ids),
    )));
}

/* ======================================================================
 *  INSTAGRAM INSIGHTS — расширенная аналитика (лента/Reels/Stories)
 *  Требуется бизнес/Creator-аккаунт IG, привязанный к Facebook-странице,
 *  и токен с правами instagram_manage_insights. Набор метрик и их названия
 *  Meta периодически меняет — при необходимости скорректируйте списки ниже.
 * ====================================================================== */

/** Человекочитаемые подписи метрик (RU/EN через языковые переменные). */
function fn_rlsv_autopost_ig_metric_labels()
{
    return array(
        'impressions'        => __('rlsv_autopost.ig_impressions'),
        'reach'              => __('rlsv_autopost.ig_reach'),
        'engagement'         => __('rlsv_autopost.ig_engagement'),
        'saved'              => __('rlsv_autopost.ig_saved'),
        'video_views'        => __('rlsv_autopost.ig_video_views'),
        'plays'              => __('rlsv_autopost.ig_plays'),
        'total_interactions' => __('rlsv_autopost.ig_total_interactions'),
        'likes'              => __('rlsv_autopost.ig_likes'),
        'comments'           => __('rlsv_autopost.ig_comments'),
        'saves'              => __('rlsv_autopost.ig_saves'),
        'shares'             => __('rlsv_autopost.ig_shares'),
        'replies'            => __('rlsv_autopost.ig_replies'),
        'taps_forward'       => __('rlsv_autopost.ig_taps_forward'),
        'taps_back'          => __('rlsv_autopost.ig_taps_back'),
        'exits'              => __('rlsv_autopost.ig_exits'),
    );
}

/**
 * Набор метрик под тип медиа:
 *  - STORY  → impressions, reach, replies, taps_forward, taps_back, exits
 *  - REELS  → plays, reach, total_interactions (+ likes/comments/saves/shares)
 *  - FEED   → impressions, reach, engagement, saved (+ video_views для видео)
 */
function fn_rlsv_autopost_ig_metrics_for_type($media_product_type, $media_type)
{
    $t = strtoupper((string) $media_product_type);

    if ($t === 'STORY') {
        return array('impressions', 'reach', 'replies', 'taps_forward', 'taps_back', 'exits');
    }
    if ($t === 'REELS') {
        return array('plays', 'reach', 'total_interactions', 'likes', 'comments', 'saves', 'shares');
    }

    $m = array('impressions', 'reach', 'engagement', 'saved');
    if (strtoupper((string) $media_type) === 'VIDEO') {
        $m[] = 'video_views';
    }
    return $m;
}

/**
 * Запрашивает insights одного медиа. Возвращает [metric => value] (пусто при ошибке/нет прав).
 */
function fn_rlsv_autopost_instagram_insights($media_id, $token, $media_product_type = 'FEED', $media_type = 'IMAGE')
{
    $media_id = trim((string) $media_id);
    if ($media_id === '' || empty($token)) {
        return array();
    }

    $metrics = fn_rlsv_autopost_ig_metrics_for_type($media_product_type, $media_type);
    $url = 'https://graph.facebook.com/v19.0/' . rawurlencode($media_id) . '/insights'
         . '?metric=' . implode(',', $metrics)
         . '&access_token=' . rawurlencode($token);

    $r    = fn_rlsv_autopost_http('GET', $url, array(), array(), 'instagram');
    $data = json_decode(isset($r['body']) ? $r['body'] : '', true);

    $out = array();
    if (!empty($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $row) {
            if (isset($row['name']) && isset($row['values'][0]['value'])) {
                $out[$row['name']] = (int) $row['values'][0]['value'];
            }
        }
    }

    return $out;
}

/**
 * Собирает аналитику Instagram для пользователя: последние публикации ленты и Reels
 * (edge /media) + активные истории (edge /stories), по каждой — метрики insights.
 * Тяжёлая операция (несколько запросов к API), поэтому вызывается на отдельной
 * странице аналитики Instagram по запросу пользователя.
 *
 * @return array{available:bool,error:string,labels:array,media:array}
 */
function fn_rlsv_autopost_get_instagram_analytics($user_id)
{
    $result = array(
        'available'   => false,
        'error'       => '',
        'labels'      => fn_rlsv_autopost_ig_metric_labels(),
        'feature_map' => array(),
        'dash'        => array(),
        'media'       => array(),
    );

    if (!(int) Registry::get('addons.rlsv_autopost.ig_fetch_insights')) {
        $result['error'] = 'disabled';
        return $result;
    }

    $tokens = fn_rlsv_autopost_get_user_tokens((int) $user_id);
    $ig_id  = isset($tokens['ig_user_id']) ? trim($tokens['ig_user_id']) : '';
    $token  = isset($tokens['ig_access_token']) ? trim($tokens['ig_access_token']) : '';
    if ($ig_id === '' || $token === '') {
        $result['error'] = 'no_tokens';
        return $result;
    }

    $limit = (int) Registry::get('addons.rlsv_autopost.ig_insights_limit');
    if ($limit <= 0) {
        $limit = 15;
    }
    $limit = min($limit, 50);

    $base        = 'https://graph.facebook.com/v19.0/';
    $type_labels = array(
        'FEED_IMAGE'    => __('rlsv_autopost.ig_type_photo'),
        'FEED_VIDEO'    => __('rlsv_autopost.ig_type_video'),
        'FEED_CAROUSEL' => __('rlsv_autopost.ig_type_carousel'),
        'REELS'         => __('rlsv_autopost.ig_type_reels'),
        'STORY'         => __('rlsv_autopost.ig_type_story'),
    );

    // Лента + Reels
    $media_url = $base . rawurlencode($ig_id) . '/media'
        . '?fields=id,media_type,media_product_type,caption,permalink,timestamp'
        . '&limit=' . $limit
        . '&access_token=' . rawurlencode($token);
    $mr = fn_rlsv_autopost_http('GET', $media_url, array(), array(), 'instagram');
    $md = json_decode(isset($mr['body']) ? $mr['body'] : '', true);

    if (isset($md['error'])) {
        $result['error'] = isset($md['error']['message']) ? $md['error']['message'] : 'api_error';
        return $result;
    }

    $items = array();
    if (!empty($md['data']) && is_array($md['data'])) {
        foreach ($md['data'] as $m) {
            $mpt = isset($m['media_product_type']) ? $m['media_product_type'] : 'FEED';
            $mt  = isset($m['media_type']) ? $m['media_type'] : 'IMAGE';

            if (strtoupper($mpt) === 'REELS') {
                $type_key = 'REELS';
            } elseif (strtoupper($mt) === 'VIDEO') {
                $type_key = 'FEED_VIDEO';
            } elseif (strtoupper($mt) === 'CAROUSEL_ALBUM') {
                $type_key = 'FEED_CAROUSEL';
            } else {
                $type_key = 'FEED_IMAGE';
            }

            $items[] = array(
                'id'         => $m['id'],
                'type_key'   => $type_key,
                'type_label' => isset($type_labels[$type_key]) ? $type_labels[$type_key] : $type_key,
                'caption'    => isset($m['caption']) ? mb_substr($m['caption'], 0, 90) : '',
                'permalink'  => isset($m['permalink']) ? $m['permalink'] : '',
                'timestamp'  => isset($m['timestamp']) ? $m['timestamp'] : '',
                'metrics'    => fn_rlsv_autopost_instagram_insights($m['id'], $token, $mpt, $mt),
            );
        }
    }

    // Истории (доступны только 24 часа)
    $stories_url = $base . rawurlencode($ig_id) . '/stories'
        . '?fields=id,media_type,media_product_type,permalink,timestamp'
        . '&access_token=' . rawurlencode($token);
    $sr = fn_rlsv_autopost_http('GET', $stories_url, array(), array(), 'instagram');
    $sd = json_decode(isset($sr['body']) ? $sr['body'] : '', true);
    if (!empty($sd['data']) && is_array($sd['data'])) {
        foreach ($sd['data'] as $s) {
            $items[] = array(
                'id'         => $s['id'],
                'type_key'   => 'STORY',
                'type_label' => $type_labels['STORY'],
                'caption'    => '',
                'permalink'  => isset($s['permalink']) ? $s['permalink'] : '',
                'timestamp'  => isset($s['timestamp']) ? $s['timestamp'] : '',
                'metrics'    => fn_rlsv_autopost_instagram_insights($s['id'], $token, 'STORY', isset($s['media_type']) ? $s['media_type'] : 'IMAGE'),
            );
        }
    }

    // Привязка «медиа → товар» + запись метрик в характеристики привязанных товаров.
    $feature_map = fn_rlsv_autopost_ig_feature_map();
    $ids = array();
    foreach ($items as $it) {
        $ids[] = $it['id'];
    }
    $product_map = fn_rlsv_autopost_get_ig_media_product_map($ids);

    foreach ($items as &$it) {
        $pid = isset($product_map[$it['id']]) ? (int) $product_map[$it['id']] : 0;
        $it['product_id'] = $pid;
        if ($pid && !empty($it['metrics']) && !empty($feature_map)) {
            fn_rlsv_autopost_write_ig_features($pid, $it['metrics']);
            db_query("UPDATE ?:rlsv_autopost_ig_media SET synced_at = ?i WHERE media_id = ?s", TIME, $it['id']);
        }
    }
    unset($it);

    $result['available']   = true;
    $result['feature_map'] = $feature_map;
    $result['dash']        = fn_rlsv_autopost_ig_build_dashboard($items);
    $result['media']       = $items;
    return $result;
}

/**
 * Карта «метрика → ID характеристики товара» из настроек (только заполненные).
 * Ключи метрик совпадают с именами полей insights Instagram.
 *
 * @return array [metric_key => feature_id(int)]
 */
function fn_rlsv_autopost_ig_feature_map()
{
    $metrics = array(
        'impressions', 'reach', 'engagement', 'saved', 'video_views',
        'plays', 'total_interactions', 'likes', 'comments', 'saves', 'shares',
        'replies', 'taps_forward', 'taps_back', 'exits',
    );

    $map = array();
    foreach ($metrics as $m) {
        $fid = (int) Registry::get('addons.rlsv_autopost.ig_feature_' . $m);
        if ($fid > 0) {
            $map[$m] = $fid;
        }
    }

    return $map;
}

/**
 * Запоминает связь «медиа Instagram → товар» (для последующей записи метрик
 * в характеристики товара). REPLACE — на случай повторной публикации.
 */
function fn_rlsv_autopost_remember_ig_media($media_id, $product_id, $user_id, $media_type = 'FEED_IMAGE')
{
    $media_id = trim((string) $media_id);
    if ($media_id === '') {
        return;
    }

    db_query("REPLACE INTO ?:rlsv_autopost_ig_media ?e", array(
        'media_id'     => $media_id,
        'product_id'   => (int) $product_id,
        'user_id'      => (int) $user_id,
        'media_type'   => (string) $media_type,
        'published_at' => TIME,
        'synced_at'    => 0,
    ));
}

/**
 * Возвращает [media_id => product_id] для переданных media_id (из карты привязок).
 *
 * @param  string[] $media_ids
 * @return array
 */
function fn_rlsv_autopost_get_ig_media_product_map(array $media_ids)
{
    $media_ids = array_filter(array_map('strval', $media_ids));
    if (empty($media_ids)) {
        return array();
    }

    return db_get_hash_single_array(
        "SELECT media_id, product_id FROM ?:rlsv_autopost_ig_media WHERE media_id IN (?a)",
        array('media_id', 'product_id'),
        $media_ids
    );
}

/**
 * Пишет значения метрик в привязанные характеристики товара согласно карте настроек.
 * $metrics — [metric_key => value] (результат insights). Записываются только те метрики,
 * для которых в настройках указан ID характеристики.
 *
 * @return int сколько характеристик записано
 */
function fn_rlsv_autopost_write_ig_features($product_id, array $metrics)
{
    $product_id = (int) $product_id;
    if (!$product_id || empty($metrics) || !function_exists('fn_update_product_features_value')) {
        return 0;
    }

    $map = fn_rlsv_autopost_ig_feature_map();
    if (empty($map)) {
        return 0;
    }

    $features = array();
    foreach ($map as $metric => $feature_id) {
        if (array_key_exists($metric, $metrics)) {
            $features[$feature_id] = (string) (int) $metrics[$metric];
        }
    }

    if (empty($features)) {
        return 0;
    }

    fn_update_product_features_value($product_id, $features, array(), CART_LANGUAGE);

    return count($features);
}

/**
 * Обновляет метрики insights по сохранённым медиа и записывает их в характеристики
 * привязанных товаров. Вызывается из крона (после дренажа очереди) и разумно
 * ограничена по количеству, чтобы не упираться в лимиты API.
 *
 * @param  int $limit сколько медиа обработать за вызов
 * @return array{processed:int,written:int}
 */
function fn_rlsv_autopost_refresh_ig_features($limit = 25)
{
    $stat = array('processed' => 0, 'written' => 0);

    // Если ни одна метрика не привязана к характеристике — писать нечего.
    if (empty(fn_rlsv_autopost_ig_feature_map())) {
        return $stat;
    }

    $limit = max(1, (int) $limit);

    // Берём медиа, у которых есть привязанный товар, начиная с самых «несвежих» по синхронизации.
    $rows = db_get_array(
        "SELECT media_id, product_id, user_id, media_type
           FROM ?:rlsv_autopost_ig_media
          WHERE product_id > 0
          ORDER BY synced_at ASC, published_at DESC
          LIMIT ?i",
        $limit
    );

    foreach ($rows as $row) {
        $tokens = fn_rlsv_autopost_get_user_tokens((int) $row['user_id']);
        $token  = isset($tokens['ig_access_token']) ? trim($tokens['ig_access_token']) : '';
        if ($token === '') {
            continue;
        }

        // media_product_type/ media_type для выбора набора метрик.
        $mpt = ($row['media_type'] === 'REELS') ? 'REELS' : (($row['media_type'] === 'STORY') ? 'STORY' : 'FEED');
        $mt  = ($row['media_type'] === 'FEED_VIDEO') ? 'VIDEO' : 'IMAGE';

        $metrics = fn_rlsv_autopost_instagram_insights($row['media_id'], $token, $mpt, $mt);
        $stat['processed']++;

        if (!empty($metrics)) {
            $stat['written'] += fn_rlsv_autopost_write_ig_features((int) $row['product_id'], $metrics);
        }

        db_query("UPDATE ?:rlsv_autopost_ig_media SET synced_at = ?i WHERE media_id = ?s", TIME, $row['media_id']);
    }

    fn_rlsv_autopost_log('ig.refresh', array('data' => $stat));

    return $stat;
}

/* ======================================================================
 *  INSTAGRAM DASHBOARD — агрегаты и геометрия графиков для bento-дашборда.
 *  Графики строятся статичным SVG (без внешних библиотек) — надёжно на витрине.
 * ====================================================================== */

/** Спарклайн (полилиния + область) по ряду значений. Возвращает готовую геометрию. */
function fn_rlsv_autopost_ig_sparkline(array $vals, $w = 300, $h = 72, $pad = 6)
{
    $vals = array_values(array_map('intval', $vals));
    $n = count($vals);
    if ($n === 0) {
        return array('has' => false);
    }
    if ($n === 1) {
        $vals = array($vals[0], $vals[0]);
        $n = 2;
    }

    $min = min($vals);
    $max = max($vals);
    $range = ($max - $min) ?: 1;
    $step = ($w - 2 * $pad) / ($n - 1);

    $pts = array();
    for ($i = 0; $i < $n; $i++) {
        $x = $pad + $i * $step;
        $y = $h - $pad - (($vals[$i] - $min) / $range) * ($h - 2 * $pad);
        $pts[] = round($x, 1) . ',' . round($y, 1);
    }

    $first = explode(',', $pts[0]);
    $last  = explode(',', $pts[$n - 1]);
    $area  = 'M ' . implode(' L ', $pts) . ' L ' . $last[0] . ',' . $h . ' L ' . $first[0] . ',' . $h . ' Z';

    return array('has' => true, 'w' => $w, 'h' => $h, 'points' => implode(' ', $pts), 'area' => $area);
}

/** Пончик (donut) из сегментов [label,value,color] через stroke-dasharray. */
function fn_rlsv_autopost_ig_donut(array $parts, $r = 26, $sw = 9)
{
    $total = 0;
    foreach ($parts as $p) {
        $total += max(0, (int) $p['value']);
    }
    $C = 2 * M_PI * $r;

    $cum = 0;
    $segs = array();
    foreach ($parts as $p) {
        $v = max(0, (int) $p['value']);
        $seg = ($total > 0) ? ($v / $total) * $C : 0;
        $segs[] = array(
            'color'     => $p['color'],
            'dasharray' => round($seg, 2) . ' ' . round($C - $seg, 2),
            'dashoffset' => round(-$cum, 2),
        );
        $cum += $seg;
    }

    return array(
        'r' => $r, 'sw' => $sw, 'size' => ($r * 2 + $sw * 2),
        'c' => ($r + $sw), 'total' => $total, 'segments' => $segs,
    );
}

/** Сводные агрегаты по типам медиа + геометрия графиков для дашборда. */
function fn_rlsv_autopost_ig_build_dashboard(array $items)
{
    $agg = array(
        'feed'    => array('impressions' => 0, 'reach' => 0, 'engagement' => 0, 'saved' => 0, 'video_views' => 0),
        'reels'   => array('plays' => 0, 'reach' => 0, 'total_interactions' => 0, 'likes' => 0, 'comments' => 0, 'saves' => 0, 'shares' => 0),
        'stories' => array('impressions' => 0, 'reach' => 0, 'replies' => 0, 'taps_forward' => 0, 'taps_back' => 0, 'exits' => 0),
    );

    $reach_series = array();
    foreach ($items as $it) {
        $g = ($it['type_key'] === 'REELS') ? 'reels' : (($it['type_key'] === 'STORY') ? 'stories' : 'feed');
        if (!empty($it['metrics'])) {
            foreach ($it['metrics'] as $k => $v) {
                if (isset($agg[$g][$k])) {
                    $agg[$g][$k] += (int) $v;
                }
            }
        }
        if ($g === 'feed' && isset($it['metrics']['reach'])) {
            $reach_series[] = (int) $it['metrics']['reach'];
        }
    }

    // Хронологический порядок (в /media новые сверху), последние до 8 точек.
    $reach_series = array_slice(array_reverse($reach_series), -8);

    $trend = null;
    if (count($reach_series) >= 2) {
        $a = $reach_series[0];
        $b = end($reach_series);
        if ($a > 0) {
            $trend = (int) round(($b - $a) / $a * 100);
        }
    }

    $donut = fn_rlsv_autopost_ig_donut(array(
        array('label' => 'likes',    'value' => $agg['reels']['likes'],    'color' => '#f2464e'),
        array('label' => 'comments', 'value' => $agg['reels']['comments'], 'color' => '#46aaf2'),
        array('label' => 'saves',    'value' => $agg['reels']['saves'],    'color' => '#27ae60'),
        array('label' => 'shares',   'value' => $agg['reels']['shares'],   'color' => '#8a97a3'),
    ));

    return array(
        'agg'         => $agg,
        'spark'       => fn_rlsv_autopost_ig_sparkline($reach_series),
        'trend'       => $trend,
        'donut'       => $donut,
        'has_feed'    => array_sum($agg['feed']) > 0,
        'has_reels'   => array_sum($agg['reels']) > 0,
        'has_stories' => array_sum($agg['stories']) > 0,
    );
}

/* ======================================================================
 *  ДАШБОРДЫ ПО ПЛОЩАДКАМ (на основе данных очереди публикаций)
 *  Переиспользуют пончик fn_rlsv_autopost_ig_donut(). Палитра — Brightness.
 * ====================================================================== */

/** Набор различимых цветов для сегментов «по площадкам» (в тонах темы). */
function fn_rlsv_autopost_chart_palette()
{
    return array('#46aaf2', '#f2464e', '#27ae60', '#fc9432', '#8a97a3', '#6f52ed', '#2bb5c0', '#e0699f');
}

/** Пончик распределения статусов done/error/pending + части для легенды. */
function fn_rlsv_autopost_status_chart($done, $error, $pending)
{
    $parts = array(
        array('label' => __('rlsv_autopost.stat_done'),    'value' => (int) $done,    'color' => '#27ae60'),
        array('label' => __('rlsv_autopost.stat_error'),   'value' => (int) $error,   'color' => '#f2464e'),
        array('label' => __('rlsv_autopost.stat_pending'), 'value' => (int) $pending, 'color' => '#fc9432'),
    );

    return array('donut' => fn_rlsv_autopost_ig_donut($parts), 'parts' => $parts);
}

/** Дашборд одной площадки: KPI + пончик статусов + процент успеха. */
function fn_rlsv_autopost_build_channel_dashboard($ch)
{
    $total   = (int) (isset($ch['total'])   ? $ch['total']   : 0);
    $done    = (int) (isset($ch['done'])    ? $ch['done']    : 0);
    $error   = (int) (isset($ch['error'])   ? $ch['error']   : 0);
    $pending = (int) (isset($ch['pending']) ? $ch['pending'] : 0);

    return array(
        'total'   => $total,
        'done'    => $done,
        'error'   => $error,
        'pending' => $pending,
        'success' => ($total > 0) ? (int) round($done / $total * 100) : 0,
        'status'  => fn_rlsv_autopost_status_chart($done, $error, $pending),
    );
}

/** Дашборд «Обзор»: KPI + пончик статусов + пончик распределения по площадкам. */
function fn_rlsv_autopost_build_overview_dashboard($stats, $platforms)
{
    $total   = (int) (isset($stats['total'])   ? $stats['total']   : 0);
    $done    = (int) (isset($stats['done'])    ? $stats['done']    : 0);
    $error   = (int) (isset($stats['error'])   ? $stats['error']   : 0);
    $pending = (int) (isset($stats['pending']) ? $stats['pending'] : 0);

    $by = (isset($stats['by_platform']) && is_array($stats['by_platform'])) ? $stats['by_platform'] : array();

    $palette = fn_rlsv_autopost_chart_palette();
    $pparts  = array();
    $i = 0;
    foreach ($by as $pf => $cnt) {
        $pparts[] = array('label' => (string) $pf, 'value' => (int) $cnt, 'color' => $palette[$i % count($palette)]);
        $i++;
    }

    return array(
        'total'    => $total,
        'done'     => $done,
        'error'    => $error,
        'pending'  => $pending,
        'success'  => ($total > 0) ? (int) round($done / $total * 100) : 0,
        'status'   => fn_rlsv_autopost_status_chart($done, $error, $pending),
        'platform' => array('donut' => fn_rlsv_autopost_ig_donut($pparts), 'parts' => $pparts),
    );
}

/* ======================================================================
 *  АНАЛИТИКА ИЗ ХАРАКТЕРИСТИК ТОВАРА
 *  Числа для графиков берутся из характеристик товаров, привязанных к
 *  пользователю напрямую (link_type=product) и/или через категорию
 *  (link_type=category). ID характеристик задаются в настройках модуля
 *  (карта fn_rlsv_autopost_ig_feature_map(): метрика => feature_id).
 * ====================================================================== */

/**
 * Список ID товаров, привязанных к пользователю: напрямую и через категории.
 * Только активные товары.
 *
 * @return int[]
 */
function fn_rlsv_autopost_get_user_product_ids($user_id)
{
    $user_id = (int) $user_id;
    if (!$user_id) {
        return array();
    }

    $direct = db_get_fields(
        "SELECT l.object_id
           FROM ?:rlsv_autopost_user_links AS l
           INNER JOIN ?:products AS p ON p.product_id = l.object_id AND p.status = 'A'
          WHERE l.user_id = ?i AND l.link_type = 'product'",
        $user_id
    );

    $by_cat = db_get_fields(
        "SELECT DISTINCT pc.product_id
           FROM ?:rlsv_autopost_user_links AS l
           INNER JOIN ?:products_categories AS pc ON pc.category_id = l.object_id
           INNER JOIN ?:products AS p ON p.product_id = pc.product_id AND p.status = 'A'
          WHERE l.user_id = ?i AND l.link_type = 'category'",
        $user_id
    );

    return array_values(array_unique(array_map('intval', array_merge((array) $direct, (array) $by_cat))));
}

/**
 * Числовое значение характеристики из строки product_features_values:
 * приоритет value_int → числовой value → числовой variant (совместимо и с
 * числовыми, и с выборочными характеристиками, где число хранится в варианте).
 */
function fn_rlsv_autopost_feature_number($row)
{
    if (isset($row['value_int']) && $row['value_int'] !== '' && $row['value_int'] !== null) {
        return (float) $row['value_int'];
    }
    foreach (array('value', 'variant') as $f) {
        if (isset($row[$f]) && $row[$f] !== '' && $row[$f] !== null) {
            $norm = str_replace(array(' ', "\xC2\xA0", ','), array('', '', '.'), (string) $row[$f]);
            if (is_numeric($norm)) {
                return (float) $norm;
            }
        }
    }
    return 0.0;
}

/**
 * Значения указанных характеристик для товара: [feature_id => число].
 * Аналог шаблонного {$product|fn_get_product_features_list} + чтение
 * $feature.value/$feature.variant, но напрямую и только по нужным feature_id.
 */
function fn_rlsv_autopost_read_product_feature_values($product_id, array $feature_ids, $lang_code = CART_LANGUAGE)
{
    $product_id  = (int) $product_id;
    $feature_ids = array_filter(array_map('intval', $feature_ids));
    if (!$product_id || empty($feature_ids)) {
        return array();
    }

    $rows = db_get_array(
        "SELECT v.feature_id, v.value, v.value_int, vd.variant
           FROM ?:product_features_values AS v
           LEFT JOIN ?:product_feature_variant_descriptions AS vd
                  ON vd.variant_id = v.variant_id AND vd.lang_code = ?s
          WHERE v.product_id = ?i AND v.feature_id IN (?n) AND v.lang_code = ?s",
        $lang_code, $product_id, $feature_ids, $lang_code
    );

    $out = array();
    foreach ($rows as $r) {
        $out[(int) $r['feature_id']] = fn_rlsv_autopost_feature_number($r);
    }

    return $out;
}

/**
 * Аналитика Instagram по данным из характеристик товаров пользователя.
 * Возвращает ту же структуру, что и API-версия (available/error/labels/
 * feature_map/dash/media), поэтому шаблон дашборда не меняется.
 */


/**
 * @return array [metric_key => feature_id(int)]
 */
function fn_rlsv_autopost_get_platform_feature_map($prefix)
{
    if ($prefix === 'yd') {
        $metrics = array(
            'impressions', 'clicks', 'cost', 'conversions', 'revenue', 'roi', 'cpa'
        );
    } else {
        $metrics = array(
            'impressions', 'reach', 'engagement', 'saved', 'video_views',
            'plays', 'total_interactions', 'likes', 'comments', 'saves', 'shares',
            'replies', 'taps_forward', 'taps_back', 'exits',
        );
    }

    $map = array();
    foreach ($metrics as $m) {
        $fid = (int) Registry::get('addons.rlsv_autopost.' . $prefix . '_feature_' . $m);
        if ($fid > 0) {
            $map[$m] = $fid;
        }
    }

    return $map;
}

function fn_rlsv_autopost_get_platform_metric_labels($prefix)
{
    if ($prefix === 'yd') {
        return array(
            'impressions' => 'Impressions',
            'clicks'      => 'Clicks',
            'cost'        => 'Cost',
            'conversions' => 'Conversions',
            'revenue'     => 'Revenue',
            'roi'         => 'ROI',
            'cpa'         => 'CPA',
        );
    }
    return fn_rlsv_autopost_ig_metric_labels();
}


/**
 * Чтение аналитики из характеристик товаров пользователя для произвольной платформы.
 */
function fn_rlsv_autopost_get_platform_feature_analytics($user_id, $prefix)
{
    $labels = fn_rlsv_autopost_get_platform_metric_labels($prefix);
    $fmap   = fn_rlsv_autopost_get_platform_feature_map($prefix);

    $result = array(
        'available'   => false,
        'error'       => '',
        'source'      => 'features',
        'labels'      => $labels,
        'feature_map' => $fmap,
        'dash'        => array(),
        'media'       => array(),
    );

    if (empty($fmap)) {
        $result['error'] = 'no_features';
        return $result;
    }

    $product_ids = fn_rlsv_autopost_get_user_product_ids((int) $user_id);
    if (empty($product_ids)) {
        $result['error'] = 'no_products';
        return $result;
    }

    $names = db_get_hash_single_array(
        "SELECT product_id, product FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code = ?s",
        array('product_id', 'product'),
        $product_ids, CART_LANGUAGE
    );

    $feature_ids   = array_values($fmap);
    $fid_to_metric = array();
    foreach ($fmap as $metric => $fid) {
        $fid_to_metric[(int) $fid] = $metric;
    }

    $totals = array();
    foreach (array_keys($fmap) as $m) {
        $totals[$m] = 0;
    }

    // Собираем значения характеристик.
    foreach ($product_ids as $pid) {
        $vals = fn_rlsv_autopost_read_product_feature_values($pid, $feature_ids);
        if (empty($vals)) {
            continue;
        }

        $metrics_row = array();
        foreach ($vals as $fid => $val) {
            $m = $fid_to_metric[$fid];
            $metrics_row[$m] = $val;
            $totals[$m] += $val;
        }

        $result['media'][] = array(
            'id'          => 'p_' . $pid,
            'product_id'  => $pid,
            'type_key'    => 'FEED_IMAGE', // Fallback for display
            'type_label'  => 'Product',
            'product_id'  => $pid,
            'metrics'     => $metrics_row,
        );
    }

    $dash = array(
        'agg' => array(
            'feed'    => $totals,
            'reels'   => $totals, // Duplicate to fill the dashboard layout
            'stories' => $totals,
        ),
        'spark' => array('has' => false),
        'donut' => array('total' => 0),
    );

    $result['available'] = true;
    $result['dash']      = $dash;

    return $result;
}

function fn_rlsv_autopost_get_feature_analytics($user_id)
{
    $labels = fn_rlsv_autopost_ig_metric_labels();
    $fmap   = fn_rlsv_autopost_ig_feature_map(); // metric => feature_id

    $result = array(
        'available'   => false,
        'error'       => '',
        'source'      => 'features',
        'labels'      => $labels,
        'feature_map' => $fmap,
        'dash'        => array(),
        'media'       => array(),
    );

    if (empty($fmap)) {
        $result['error'] = 'no_features';
        return $result;
    }

    $product_ids = fn_rlsv_autopost_get_user_product_ids((int) $user_id);
    if (empty($product_ids)) {
        $result['error'] = 'no_products';
        return $result;
    }

    $names = db_get_hash_single_array(
        "SELECT product_id, product FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code = ?s",
        array('product_id', 'product'),
        $product_ids, CART_LANGUAGE
    );

    $feature_ids   = array_values($fmap);
    $fid_to_metric = array();
    foreach ($fmap as $metric => $fid) {
        $fid_to_metric[(int) $fid] = $metric;
    }

    $totals = array();
    foreach (array_keys($fmap) as $m) {
        $totals[$m] = 0;
    }

    $reach_series = array();
    $items = array();

    foreach ($product_ids as $pid) {
        $vals = fn_rlsv_autopost_read_product_feature_values($pid, $feature_ids);
        $pm = array();
        foreach ($vals as $fid => $num) {
            if (isset($fid_to_metric[$fid])) {
                $metric = $fid_to_metric[$fid];
                $pm[$metric] = (int) round($num);
                $totals[$metric] += (int) round($num);
            }
        }
        if (!empty($pm)) {
            if (isset($pm['reach'])) {
                $reach_series[] = (int) $pm['reach'];
            }
            $items[] = array(
                'id'         => 'p' . $pid,
                'type_key'   => 'FEED',
                'type_label' => isset($names[$pid]) ? $names[$pid] : ('#' . $pid),
                'caption'    => '',
                'permalink'  => '',
                'timestamp'  => '',
                'product_id' => (int) $pid,
                'metrics'    => $pm,
            );
        }
    }

    $tv = function ($k) use ($totals) {
        return isset($totals[$k]) ? (int) $totals[$k] : 0;
    };

    $agg = array(
        'feed' => array(
            'impressions' => $tv('impressions'), 'reach' => $tv('reach'),
            'engagement' => $tv('engagement'), 'saved' => $tv('saved'), 'video_views' => $tv('video_views'),
        ),
        'reels' => array(
            'plays' => $tv('plays'), 'reach' => $tv('reach'), 'total_interactions' => $tv('total_interactions'),
            'likes' => $tv('likes'), 'comments' => $tv('comments'), 'saves' => $tv('saves'), 'shares' => $tv('shares'),
        ),
        'stories' => array(
            'impressions' => $tv('impressions'), 'reach' => $tv('reach'), 'replies' => $tv('replies'),
            'taps_forward' => $tv('taps_forward'), 'taps_back' => $tv('taps_back'), 'exits' => $tv('exits'),
        ),
    );

    $reach_series = array_slice($reach_series, -8);
    $trend = null;
    if (count($reach_series) >= 2 && $reach_series[0] > 0) {
        $trend = (int) round((end($reach_series) - $reach_series[0]) / $reach_series[0] * 100);
    }

    $donut = fn_rlsv_autopost_ig_donut(array(
        array('label' => 'likes',    'value' => $tv('likes'),    'color' => '#f2464e'),
        array('label' => 'comments', 'value' => $tv('comments'), 'color' => '#46aaf2'),
        array('label' => 'saves',    'value' => $tv('saves'),    'color' => '#27ae60'),
        array('label' => 'shares',   'value' => $tv('shares'),   'color' => '#8a97a3'),
    ));

    $result['available'] = true;
    $result['dash'] = array(
        'agg'         => $agg,
        'spark'       => fn_rlsv_autopost_ig_sparkline($reach_series),
        'trend'       => $trend,
        'donut'       => $donut,
        'has_feed'    => array_sum($agg['feed']) > 0,
        'has_reels'   => array_sum($agg['reels']) > 0,
        'has_stories' => array_sum($agg['stories']) > 0,
    );
    $result['media'] = $items;

    return $result;
}

/* ======================================================================
 *  УСТАНОВКА/ОБНОВЛЕНИЕ СХЕМЫ БД
 *  Гарантирует наличие всех таблиц и столбцов модуля. Вызывается при
 *  установке и включении аддона (hook fn_settings_actions_addons_*),
 *  а также безопасно для повторного вызова (идемпотентно).
 * ====================================================================== */

/** Проверка наличия столбца в таблице (для идемпотентных миграций). */
function fn_rlsv_autopost_column_exists($table, $column)
{
    $cols = db_get_fields("SHOW COLUMNS FROM ?:" . str_replace('?:', '', $table));
    return in_array($column, (array) $cols, true);
}

/**
 * Создаёт недостающие таблицы модуля и добавляет недостающие столбцы.
 * Безопасно вызывать многократно: используются CREATE TABLE IF NOT EXISTS
 * и ALTER TABLE ADD COLUMN только при отсутствии столбца.
 */
function fn_rlsv_autopost_ensure_schema()
{
    $tables = array();

    $tables[] = "CREATE TABLE IF NOT EXISTS `?:rlsv_autopost_queue` (
        `queue_id`     int(11) unsigned NOT NULL AUTO_INCREMENT,
        `product_id`   int(11) unsigned NOT NULL DEFAULT '0',
        `user_id`      int(11) unsigned NOT NULL DEFAULT '0',
        `platform`     varchar(32) NOT NULL DEFAULT '',
        `status`       varchar(16) NOT NULL DEFAULT 'pending',
        `payload`      mediumtext,
        `attempts`     tinyint(3) unsigned NOT NULL DEFAULT '0',
        `error`        text,
        `run_after`    int(11) unsigned NOT NULL DEFAULT '0',
        `created_at`   int(11) unsigned NOT NULL DEFAULT '0',
        `processed_at` int(11) unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY (`queue_id`),
        KEY `status` (`status`),
        KEY `run_after` (`run_after`),
        KEY `product_user_platform` (`product_id`,`user_id`,`platform`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $tables[] = "CREATE TABLE IF NOT EXISTS `?:rlsv_autopost_tokens` (
        `user_id` int(11) unsigned NOT NULL,
        `tokens`  text,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $tables[] = "CREATE TABLE IF NOT EXISTS `?:rlsv_autopost_products` (
        `product_id`   int(11) unsigned NOT NULL,
        `autopost`     char(1) NOT NULL DEFAULT 'Y',
        `video_url`    varchar(255) NOT NULL DEFAULT '',
        `pub_media`    char(1) NOT NULL DEFAULT 'Y',
        `pub_carousel` char(1) NOT NULL DEFAULT 'N',
        `pub_stories`  char(1) NOT NULL DEFAULT 'N',
        PRIMARY KEY (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $tables[] = "CREATE TABLE IF NOT EXISTS `?:rlsv_autopost_categories` (
        `category_id` int(11) unsigned NOT NULL,
        `autopost`    char(1) NOT NULL DEFAULT 'N',
        PRIMARY KEY (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $tables[] = "CREATE TABLE IF NOT EXISTS `?:rlsv_autopost_user_links` (
        `user_id`   int(11) unsigned NOT NULL,
        `link_type` varchar(16) NOT NULL DEFAULT 'product',
        `object_id` int(11) unsigned NOT NULL,
        PRIMARY KEY (`user_id`,`link_type`,`object_id`),
        KEY `link_lookup` (`link_type`,`object_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $tables[] = "CREATE TABLE IF NOT EXISTS `?:rlsv_autopost_ig_media` (
        `media_id`     varchar(64) NOT NULL,
        `product_id`   int(11) unsigned NOT NULL DEFAULT 0,
        `user_id`      int(11) unsigned NOT NULL DEFAULT 0,
        `media_type`   varchar(20) NOT NULL DEFAULT 'FEED_IMAGE',
        `published_at` int(11) unsigned NOT NULL DEFAULT 0,
        `synced_at`    int(11) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`media_id`),
        KEY `product_id` (`product_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    foreach ($tables as $sql) {
        db_query($sql);
    }

    // Досоздание столбцов для баз, созданных предыдущими версиями схемы.
    $add_columns = array(
        'rlsv_autopost_products' => array(
            'pub_media'    => "ALTER TABLE `?:rlsv_autopost_products` ADD COLUMN `pub_media` char(1) NOT NULL DEFAULT 'Y'",
            'pub_carousel' => "ALTER TABLE `?:rlsv_autopost_products` ADD COLUMN `pub_carousel` char(1) NOT NULL DEFAULT 'N'",
            'pub_stories'  => "ALTER TABLE `?:rlsv_autopost_products` ADD COLUMN `pub_stories` char(1) NOT NULL DEFAULT 'N'",
        ),
        'rlsv_autopost_ig_media' => array(
            'synced_at' => "ALTER TABLE `?:rlsv_autopost_ig_media` ADD COLUMN `synced_at` int(11) unsigned NOT NULL DEFAULT 0",
        ),
    );

    foreach ($add_columns as $table => $columns) {
        foreach ($columns as $column => $sql) {
            if (!fn_rlsv_autopost_column_exists($table, $column)) {
                db_query($sql);
            }
        }
    }
}

/**
 * Хук смены статуса аддона (вызывается ядром при установке и включении/выключении).
 * Во время установки/включения загружается func.php (fn_get_schema force init),
 * поэтому функция доступна и на этапе установки. Гарантирует создание нужных
 * таблиц/столбцов, если их ещё нет в базе.
 */
function fn_settings_actions_addons_rlsv_autopost($new_status, $old_status, $on_install = false)
{
    if ($new_status === 'A' || $on_install) {
        fn_rlsv_autopost_ensure_schema();
    }
}
