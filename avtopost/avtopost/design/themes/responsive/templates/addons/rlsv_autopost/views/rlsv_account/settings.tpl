{*
    Витрина: отдельная страница настроек API и прокси текущего пользователя.
    dispatch=rlsv_account.settings (POST сохраняет через тот же контроллер).
    Поля вынесены в общий партиал settings_fields.tpl (тот же блок во вкладке профиля).
*}
{capture name="mainbox"}
{assign var="t" value=$rlsv_tokens}
<div class="rlsv-ap">

    <p class="ty-mb-s">{__("rlsv_autopost.settings_intro")}</p>

    <form action="{"rlsv_account.settings"|fn_url}" method="post" name="rlsv_settings_form">
        <input type="hidden" name="rlsv_csrf" value="{$rlsv_csrf|escape}" />

        {include file="addons/rlsv_autopost/views/rlsv_account/settings_fields.tpl" t=$t}

        <div class="buttons-container">
            <button type="submit" class="ty-btn ty-btn__primary">{__("save")}</button>
            <a class="ty-btn" href="{"rlsv_account.stats"|fn_url}">{__("rlsv_autopost.my_stats")}</a>
        </div>
    </form>

</div>
{/capture}

{$smarty.capture.mainbox nofilter}
{capture name="mainbox_title"}{__("rlsv_autopost.my_settings")}{/capture}
