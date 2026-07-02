{*
    Витрина: вкладка «Автопостинг» в карточке пользователя (личный кабинет).
    Хук: profiles:tabs  (подтверждён в теме: views/profiles/update.tpl).
    Вкладку регистрирует controllers/frontend/profiles.post.php (navigation.tabs.rlsv_autopost).
    Форма отправляется в rlsv_account.settings (CSRF + белый список + валидация прокси),
    после сохранения пользователь возвращается на эту же вкладку (rlsv_return=profile).
*}
{if $auth.user_id}
    {assign var="t" value=$rlsv_autopost_tokens}
    <div id="content_rlsv_autopost" class="ty-account">
        <form action="{"rlsv_account.settings"|fn_url}" method="post" name="rlsv_profile_tokens_form">
            <input type="hidden" name="rlsv_csrf" value="{$rlsv_csrf|escape}" />
            <input type="hidden" name="rlsv_return" value="profile" />

            <p class="ty-mb-s">{__("rlsv_autopost.settings_intro")}</p>

            {include file="addons/rlsv_autopost/views/rlsv_account/settings_fields.tpl" t=$t}

            <div class="ty-profile-field__buttons buttons-container">
                <button type="submit" class="ty-btn ty-btn__primary">{__("save")}</button>
                <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.stats"|fn_url}">{__("rlsv_autopost.my_stats")}</a>
            </div>
        </form>
    <!--content_rlsv_autopost--></div>
{/if}
