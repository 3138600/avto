<?php
/**
 * rlsv_autopost — пункт меню «Автопостинг: статистика» в разделе «Покупатели».
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$schema['central']['customers']['items']['rlsv_autopost_stats'] = array(
    'attrs' => array(
        'class' => 'is-addon',
    ),
    'href'     => 'rlsv_autopost.stats',
    'position' => 510,
);

return $schema;
