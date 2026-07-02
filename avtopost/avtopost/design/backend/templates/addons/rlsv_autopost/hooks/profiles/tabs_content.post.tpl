{*
    Вкладка «Автопостинг» в карточке администратора — токены площадок.
    Хук: profiles:tabs_content  (регистрируется в controllers/backend/profiles.post.php)
    Поля кладём в отдельный ключ rlsv_tokens[...] (НЕ user_data), сохраняет хук update_user.
*}
<div id="content_rlsv_autopost">
    {assign var="t" value=$rlsv_autopost_tokens}

    <fieldset>
        <legend>{__("rlsv_autopost.binding_legend")}</legend>

        <div class="control-group">
            <label class="control-label">{__("rlsv_autopost.bound_categories")}</label>
            <div class="controls">
                {include file="pickers/categories/picker.tpl"
                    input_name="rlsv_autopost_user[categories]"
                    item_ids=$rlsv_autopost_user.categories
                    multiple=true}
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">{__("rlsv_autopost.bound_products")}</label>
            <div class="controls">
                {include file="pickers/products/picker.tpl"
                    input_name="rlsv_autopost_user[products]"
                    data_id="rlsv_autopost_products"
                    item_ids=$rlsv_autopost_user.products
                    type="links"}
            </div>
        </div>

        {if $user_data.user_id}
            <div class="control-group">
                <div class="controls">
                    <a class="btn" href="{"rlsv_autopost.stats?user_id=`$user_data.user_id`"|fn_url}">{__("rlsv_autopost.stats")}</a>
                </div>
            </div>
        {/if}
    </fieldset>

    <p class="muted">{__("rlsv_autopost.tokens_hint")}</p>

    <fieldset>
        <legend>{__("rlsv_autopost.proxy_legend")}</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_proxy">{__("rlsv_autopost.proxy")}</label>
            <div class="controls">
                <input type="text" id="rlsv_proxy" name="rlsv_tokens[proxy]" value="{$t.proxy}" class="input-large" placeholder="http://user:pass@host:port" />
                <p class="muted">{__("rlsv_autopost.proxy_hint")}</p>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>ВКонтакте (wall.post)</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_vk_access_token">Access token</label>
            <div class="controls"><input type="text" id="rlsv_vk_access_token" name="rlsv_tokens[vk_access_token]" value="{$t.vk_access_token}" class="input-large" autocomplete="off" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_vk_owner_id">Owner ID (сообщество со знаком «-»)</label>
            <div class="controls"><input type="text" id="rlsv_vk_owner_id" name="rlsv_tokens[vk_owner_id]" value="{$t.vk_owner_id}" class="input-large" /></div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Instagram (Graph API)</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_ig_access_token">Access token</label>
            <div class="controls"><input type="text" id="rlsv_ig_access_token" name="rlsv_tokens[ig_access_token]" value="{$t.ig_access_token}" class="input-large" autocomplete="off" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_ig_user_id">IG User ID (business)</label>
            <div class="controls"><input type="text" id="rlsv_ig_user_id" name="rlsv_tokens[ig_user_id]" value="{$t.ig_user_id}" class="input-large" /></div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Telegram (Bot API)</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_tg_bot_token">Bot token</label>
            <div class="controls"><input type="text" id="rlsv_tg_bot_token" name="rlsv_tokens[tg_bot_token]" value="{$t.tg_bot_token}" class="input-large" autocomplete="off" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_tg_chat_id">Chat ID канала (@channel или -100…)</label>
            <div class="controls"><input type="text" id="rlsv_tg_chat_id" name="rlsv_tokens[tg_chat_id]" value="{$t.tg_chat_id}" class="input-large" /></div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Одноклассники (mediatopic.post)</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_ok_access_token">Access token</label>
            <div class="controls"><input type="text" id="rlsv_ok_access_token" name="rlsv_tokens[ok_access_token]" value="{$t.ok_access_token}" class="input-large" autocomplete="off" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_ok_application_key">Application key</label>
            <div class="controls"><input type="text" id="rlsv_ok_application_key" name="rlsv_tokens[ok_application_key]" value="{$t.ok_application_key}" class="input-large" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_ok_session_secret_key">Session secret key</label>
            <div class="controls"><input type="text" id="rlsv_ok_session_secret_key" name="rlsv_tokens[ok_session_secret_key]" value="{$t.ok_session_secret_key}" class="input-large" autocomplete="off" /></div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Pinterest (API v5)</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_pin_access_token">Access token</label>
            <div class="controls"><input type="text" id="rlsv_pin_access_token" name="rlsv_tokens[pin_access_token]" value="{$t.pin_access_token}" class="input-large" autocomplete="off" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_pin_board_id">Board ID</label>
            <div class="controls"><input type="text" id="rlsv_pin_board_id" name="rlsv_tokens[pin_board_id]" value="{$t.pin_board_id}" class="input-large" /></div>
        </div>
    </fieldset>

    <fieldset>
        <legend>МАКС (Webhook)</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_max_webhook_url">Webhook URL</label>
            <div class="controls"><input type="text" id="rlsv_max_webhook_url" name="rlsv_tokens[max_webhook_url]" value="{$t.max_webhook_url}" class="input-large" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_max_token">Bearer token (если нужен)</label>
            <div class="controls"><input type="text" id="rlsv_max_token" name="rlsv_tokens[max_token]" value="{$t.max_token}" class="input-large" autocomplete="off" /></div>
        </div>
    </fieldset>

    <fieldset>
        <legend>YouTube (Data API v3)</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_yt_client_id">OAuth Client ID</label>
            <div class="controls"><input type="text" id="rlsv_yt_client_id" name="rlsv_tokens[yt_client_id]" value="{$t.yt_client_id}" class="input-large" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_yt_client_secret">OAuth Client Secret</label>
            <div class="controls"><input type="text" id="rlsv_yt_client_secret" name="rlsv_tokens[yt_client_secret]" value="{$t.yt_client_secret}" class="input-large" autocomplete="off" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_yt_refresh_token">Refresh token</label>
            <div class="controls"><input type="text" id="rlsv_yt_refresh_token" name="rlsv_tokens[yt_refresh_token]" value="{$t.yt_refresh_token}" class="input-large" autocomplete="off" /></div>
        </div>
    </fieldset>

    <fieldset>
        <legend>RuTube (Partner API)</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_rt_access_token">Access token</label>
            <div class="controls"><input type="text" id="rlsv_rt_access_token" name="rlsv_tokens[rt_access_token]" value="{$t.rt_access_token}" class="input-large" autocomplete="off" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_rt_category_id">Category ID (необязательно)</label>
            <div class="controls"><input type="text" id="rlsv_rt_category_id" name="rlsv_tokens[rt_category_id]" value="{$t.rt_category_id}" class="input-large" /></div>
        </div>
    </fieldset>
</div>
