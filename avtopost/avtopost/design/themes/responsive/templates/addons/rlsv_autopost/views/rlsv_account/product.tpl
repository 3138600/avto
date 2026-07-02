{*
    Витрина: отдельная страница редактирования контента товара.
    dispatch=rlsv_account.product?product_id=XXX  (доступ проверяет контроллер).
    Классы витринные (ty-control-group / ty-input-text / ty-input-textarea).
*}
{capture name="mainbox"}
{assign var="p" value=$rlsv_autopost_product}
<div class="rlsv-ap ty-account">

    {if $rlsv_product}
        <p class="ty-mb-s">
            <strong>{$rlsv_product.product|escape}</strong>
            &mdash; <a href="{"products.view?product_id=`$rlsv_product.product_id`"|fn_url}" target="_blank" rel="noopener">{__("rlsv_autopost.open_product")}</a>
        </p>
    {/if}

    <form action="{"rlsv_account.product"|fn_url}" method="post" name="rlsv_product_form">
        <input type="hidden" name="rlsv_csrf" value="{$rlsv_csrf|escape}" />
        <input type="hidden" name="product_id" value="{$rlsv_product.product_id|default:''}" />
        <input type="hidden" name="rlsv_return" value="page" />

        <div class="ty-control-group">
            <label class="ty-control-group__title" for="rlsv_full_description">{__("rlsv_autopost.full_description")}</label>
            <textarea id="rlsv_full_description" name="rlsv_full_description" rows="14" class="ty-input-textarea" style="width:100%;">{$rlsv_full_description|escape}</textarea>
            <p class="ty-control-group__description">{__("rlsv_autopost.full_description_hint")}</p>
        </div>

        <fieldset class="ty-profile-fieldset">
            <legend>{__("rlsv_autopost.tab")}</legend>
            <div class="ty-control-group">
                <label class="ty-control-group__title" for="rlsv_ap_autopost">{__("rlsv_autopost.publish_product")}</label>
                <input type="hidden" name="rlsv_autopost_product[autopost]" value="N" />
                <input type="checkbox" id="rlsv_ap_autopost" name="rlsv_autopost_product[autopost]" value="Y"
                       {if !$p.autopost || $p.autopost == "Y"}checked="checked"{/if} />
            </div>
            <div class="ty-control-group">
                <label class="ty-control-group__title" for="rlsv_ap_video">{__("rlsv_autopost.product_video_url")}</label>
                <input type="text" id="rlsv_ap_video" name="rlsv_autopost_product[video_url]" value="{$p.video_url|escape}" class="ty-input-text" />
            </div>
        </fieldset>

        <fieldset class="ty-profile-fieldset">
            <legend>{__("rlsv_autopost.pub_tab_type")}</legend>
            <style>
                #rlsv-pubtype .rlsv-toggle { display:flex; align-items:center; gap:12px; padding:10px 0; }
                #rlsv-pubtype .rlsv-sw { position:relative; display:inline-block; width:48px; height:26px; flex:none; cursor:pointer; }
                #rlsv-pubtype .rlsv-sw input { position:absolute; opacity:0; width:0; height:0; }
                #rlsv-pubtype .rlsv-sw-track { position:absolute; top:0; left:0; right:0; bottom:0; background:#cdd9e0; border-radius:26px; transition:background .2s ease; }
                #rlsv-pubtype .rlsv-sw-thumb { position:absolute; top:3px; left:3px; width:20px; height:20px; background:#fff; border-radius:50%; box-shadow:0 1px 2px rgba(0,0,0,.3); transition:left .2s ease; }
                #rlsv-pubtype .rlsv-sw input[type="checkbox"]:checked ~ .rlsv-sw-track { background:#46aaf2; }
                #rlsv-pubtype .rlsv-sw input[type="checkbox"]:checked ~ .rlsv-sw-track .rlsv-sw-thumb { left:25px; }
                #rlsv-pubtype .rlsv-toggle-label { font-size:14px; color:#465363; }
            </style>
            <div id="rlsv-pubtype">
                <p class="ty-control-group__description ty-mb-s">{__("rlsv_autopost.pub_type_hint")}</p>
                <div class="rlsv-toggle">
                    <label class="rlsv-sw">
                        <input type="hidden" name="rlsv_autopost_product[pub_media]" value="N" />
                        <input type="checkbox" name="rlsv_autopost_product[pub_media]" value="Y" {if $p.pub_media == "Y"}checked="checked"{/if} />
                        <span class="rlsv-sw-track"><span class="rlsv-sw-thumb"></span></span>
                    </label>
                    <span class="rlsv-toggle-label">{__("rlsv_autopost.pub_media")}</span>
                </div>
                <div class="rlsv-toggle">
                    <label class="rlsv-sw">
                        <input type="hidden" name="rlsv_autopost_product[pub_carousel]" value="N" />
                        <input type="checkbox" name="rlsv_autopost_product[pub_carousel]" value="Y" {if $p.pub_carousel == "Y"}checked="checked"{/if} />
                        <span class="rlsv-sw-track"><span class="rlsv-sw-thumb"></span></span>
                    </label>
                    <span class="rlsv-toggle-label">{__("rlsv_autopost.pub_carousel")}</span>
                </div>
                <div class="rlsv-toggle">
                    <label class="rlsv-sw">
                        <input type="hidden" name="rlsv_autopost_product[pub_stories]" value="N" />
                        <input type="checkbox" name="rlsv_autopost_product[pub_stories]" value="Y" {if $p.pub_stories == "Y"}checked="checked"{/if} />
                        <span class="rlsv-sw-track"><span class="rlsv-sw-thumb"></span></span>
                    </label>
                    <span class="rlsv-toggle-label">{__("rlsv_autopost.pub_stories")}</span>
                </div>
            </div>
        </fieldset>

        <div class="ty-profile-field__buttons buttons-container">
            <button type="submit" class="ty-btn ty-btn__primary">{__("save")}</button>
            <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.stats"|fn_url}">{__("rlsv_autopost.my_stats")}</a>
        </div>
    </form>

</div>
{/capture}

{$smarty.capture.mainbox nofilter}
{capture name="mainbox_title"}{__("rlsv_autopost.edit_description")}{/capture}
