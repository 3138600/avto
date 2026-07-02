{*
    Витрина: пункты меню «Мой аккаунт» → настройки автопостинга и аналитика.
    Хук: profiles:my_account_menu (подтверждён в blocks/my_account.tpl темы responsive).
    Показываются только авторизованному пользователю.
*}
{if $auth.user_id}
    <li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a underlined" href="{"rlsv_account.settings"|fn_url}" rel="nofollow">{__("rlsv_autopost.my_settings")}</a></li>
    <li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a underlined" href="{"rlsv_account.stats"|fn_url}" rel="nofollow">{__("rlsv_autopost.my_stats")}</a></li>
    <li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a underlined" href="{"rlsv_account.instagram"|fn_url}" rel="nofollow">{__("rlsv_autopost.ig_analytics")}</a></li>
{/if}
