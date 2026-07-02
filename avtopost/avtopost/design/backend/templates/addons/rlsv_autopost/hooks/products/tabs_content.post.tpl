{*
    Вкладка «Автопостинг» в карточке товара.
    Хук: products:tabs_content  (вкладка регистрируется в controllers/backend/products.post.php)
    - Пер-товарные поля кладём в rlsv_autopost_product[...] (НЕ в product_data).
    - Привязка издателей к товару — rlsv_autopost_publishers (сохраняется в products.post.php).
*}
<div id="content_rlsv_autopost">
    {assign var="p" value=$rlsv_autopost_product}

    <div class="control-group">
        <label class="control-label" for="rlsv_ap_autopost">{__("rlsv_autopost.publish_product")}</label>
        <div class="controls">
            <input type="hidden" name="rlsv_autopost_product[autopost]" value="N" />
            <input type="checkbox" id="rlsv_ap_autopost" name="rlsv_autopost_product[autopost]" value="Y"
                   {if !$p.autopost || $p.autopost == "Y"}checked="checked"{/if} />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="rlsv_ap_video">{__("rlsv_autopost.product_video_url")}</label>
        <div class="controls">
            <input type="text" id="rlsv_ap_video" name="rlsv_autopost_product[video_url]"
                   value="{$p.video_url}" class="input-large" />
        </div>
    </div>

    <fieldset>
        <legend>{__("rlsv_autopost.pub_tab_type")}</legend>
        <div class="control-group">
            <label class="control-label" for="rlsv_ap_pub_media">{__("rlsv_autopost.pub_media")}</label>
            <div class="controls">
                <input type="hidden" name="rlsv_autopost_product[pub_media]" value="N" />
                <input type="checkbox" id="rlsv_ap_pub_media" name="rlsv_autopost_product[pub_media]" value="Y"
                       {if $p.pub_media == "Y"}checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_ap_pub_carousel">{__("rlsv_autopost.pub_carousel")}</label>
            <div class="controls">
                <input type="hidden" name="rlsv_autopost_product[pub_carousel]" value="N" />
                <input type="checkbox" id="rlsv_ap_pub_carousel" name="rlsv_autopost_product[pub_carousel]" value="Y"
                       {if $p.pub_carousel == "Y"}checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rlsv_ap_pub_stories">{__("rlsv_autopost.pub_stories")}</label>
            <div class="controls">
                <input type="hidden" name="rlsv_autopost_product[pub_stories]" value="N" />
                <input type="checkbox" id="rlsv_ap_pub_stories" name="rlsv_autopost_product[pub_stories]" value="Y"
                       {if $p.pub_stories == "Y"}checked="checked"{/if} />
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>{__("rlsv_autopost.bound_publishers")}</legend>
        <div class="control-group">
            <label class="control-label">{__("rlsv_autopost.bound_publishers")}</label>
            <div class="controls">
                {include file="pickers/users/picker.tpl"
                    input_name="rlsv_autopost_publishers"
                    data_id="rlsv_autopost_publishers"
                    item_ids=$rlsv_autopost_product_publishers
                    display="checkbox"}
                <p class="muted">{__("rlsv_autopost.bound_publishers_hint")}</p>
            </div>
        </div>
    </fieldset>
</div>
