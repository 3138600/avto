{*
    Общий блок полей токенов площадок + прокси (витрина).
    Подключается на странице rlsv_account.settings и во вкладке «Автопостинг» профиля.
    Классы — витринные (ty-control-group / ty-input-text). Ожидает переменную $t.
*}
<fieldset class="ty-profile-fieldset">
    <legend>{__("rlsv_autopost.proxy_legend")}</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_proxy">{__("rlsv_autopost.proxy")}</label>
        <input type="text" id="rlsv_proxy" name="rlsv_tokens[proxy]" value="{$t.proxy|escape}" class="ty-input-text" placeholder="http://user:pass@host:port" />
        <p class="ty-control-group__description">{__("rlsv_autopost.proxy_hint")}</p>
    </div>
</fieldset>

<fieldset class="ty-profile-fieldset">
    <legend>ВКонтакте (wall.post)</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_vk_access_token">Access token</label>
        <input type="text" id="rlsv_vk_access_token" name="rlsv_tokens[vk_access_token]" value="{$t.vk_access_token|escape}" class="ty-input-text" autocomplete="off" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_vk_owner_id">Owner ID (сообщество со знаком «-»)</label>
        <input type="text" id="rlsv_vk_owner_id" name="rlsv_tokens[vk_owner_id]" value="{$t.vk_owner_id|escape}" class="ty-input-text" />
    </div>
</fieldset>

<fieldset class="ty-profile-fieldset">
    <legend>Instagram (Graph API)</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_ig_access_token">Access token</label>
        <input type="text" id="rlsv_ig_access_token" name="rlsv_tokens[ig_access_token]" value="{$t.ig_access_token|escape}" class="ty-input-text" autocomplete="off" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_ig_user_id">IG User ID (business)</label>
        <input type="text" id="rlsv_ig_user_id" name="rlsv_tokens[ig_user_id]" value="{$t.ig_user_id|escape}" class="ty-input-text" />
    </div>
</fieldset>

<fieldset class="ty-profile-fieldset">
    <legend>Telegram (Bot API)</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_tg_bot_token">Bot token</label>
        <input type="text" id="rlsv_tg_bot_token" name="rlsv_tokens[tg_bot_token]" value="{$t.tg_bot_token|escape}" class="ty-input-text" autocomplete="off" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_tg_chat_id">Chat ID канала (@channel или -100…)</label>
        <input type="text" id="rlsv_tg_chat_id" name="rlsv_tokens[tg_chat_id]" value="{$t.tg_chat_id|escape}" class="ty-input-text" />
    </div>
</fieldset>

<fieldset class="ty-profile-fieldset">
    <legend>Одноклассники (mediatopic.post)</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_ok_access_token">Access token</label>
        <input type="text" id="rlsv_ok_access_token" name="rlsv_tokens[ok_access_token]" value="{$t.ok_access_token|escape}" class="ty-input-text" autocomplete="off" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_ok_application_key">Application key</label>
        <input type="text" id="rlsv_ok_application_key" name="rlsv_tokens[ok_application_key]" value="{$t.ok_application_key|escape}" class="ty-input-text" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_ok_session_secret_key">Session secret key</label>
        <input type="text" id="rlsv_ok_session_secret_key" name="rlsv_tokens[ok_session_secret_key]" value="{$t.ok_session_secret_key|escape}" class="ty-input-text" autocomplete="off" />
    </div>
</fieldset>

<fieldset class="ty-profile-fieldset">
    <legend>Pinterest (API v5)</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_pin_access_token">Access token</label>
        <input type="text" id="rlsv_pin_access_token" name="rlsv_tokens[pin_access_token]" value="{$t.pin_access_token|escape}" class="ty-input-text" autocomplete="off" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_pin_board_id">Board ID</label>
        <input type="text" id="rlsv_pin_board_id" name="rlsv_tokens[pin_board_id]" value="{$t.pin_board_id|escape}" class="ty-input-text" />
    </div>
</fieldset>

<fieldset class="ty-profile-fieldset">
    <legend>МАКС (Webhook)</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_max_webhook_url">Webhook URL</label>
        <input type="text" id="rlsv_max_webhook_url" name="rlsv_tokens[max_webhook_url]" value="{$t.max_webhook_url|escape}" class="ty-input-text" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_max_token">Bearer token (если нужен)</label>
        <input type="text" id="rlsv_max_token" name="rlsv_tokens[max_token]" value="{$t.max_token|escape}" class="ty-input-text" autocomplete="off" />
    </div>
</fieldset>

<fieldset class="ty-profile-fieldset">
    <legend>YouTube (Data API v3)</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_yt_client_id">OAuth Client ID</label>
        <input type="text" id="rlsv_yt_client_id" name="rlsv_tokens[yt_client_id]" value="{$t.yt_client_id|escape}" class="ty-input-text" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_yt_client_secret">OAuth Client Secret</label>
        <input type="text" id="rlsv_yt_client_secret" name="rlsv_tokens[yt_client_secret]" value="{$t.yt_client_secret|escape}" class="ty-input-text" autocomplete="off" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_yt_refresh_token">Refresh token</label>
        <input type="text" id="rlsv_yt_refresh_token" name="rlsv_tokens[yt_refresh_token]" value="{$t.yt_refresh_token|escape}" class="ty-input-text" autocomplete="off" />
    </div>
</fieldset>

<fieldset class="ty-profile-fieldset">
    <legend>RuTube (Partner API)</legend>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_rt_access_token">Access token</label>
        <input type="text" id="rlsv_rt_access_token" name="rlsv_tokens[rt_access_token]" value="{$t.rt_access_token|escape}" class="ty-input-text" autocomplete="off" />
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="rlsv_rt_category_id">Category ID (необязательно)</label>
        <input type="text" id="rlsv_rt_category_id" name="rlsv_tokens[rt_category_id]" value="{$t.rt_category_id|escape}" class="ty-input-text" />
    </div>
</fieldset>
