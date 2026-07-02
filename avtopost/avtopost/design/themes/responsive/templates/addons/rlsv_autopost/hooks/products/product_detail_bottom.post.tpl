{*
    Витрина: редактор автопостинга на карточке товара (для товаров, подлежащих
    автопостингу). Хук: products:product_detail_bottom.
    Виден пользователю, которому товар назначен ($rlsv_can_edit).
    Две вкладки: «Контент» и «Тип публикации» (тумблеры Media/Carousel/Stories).
*}
{if $rlsv_can_edit}
    {assign var="p" value=$rlsv_autopost_product}
    <div id="rlsv-card-editor" class="rlsv-ap rlsv-ap-inline ty-account ty-mb-l">
        <style>
            #rlsv-card-editor { border:1px solid #e1e9ee; border-radius:12px; padding:16px 18px; margin-top:20px; background:#fff; box-shadow:0 1px 3px 0 rgba(70,83,99,.08); }
            #rlsv-card-editor .rlsv-it { list-style:none; margin:0 0 16px; padding:0; border-bottom:1px solid #d3dde3; display:flex; flex-wrap:wrap; }
            #rlsv-card-editor .rlsv-it li { margin:0 4px 0 0; }
            #rlsv-card-editor .rlsv-it a { display:block; padding:9px 16px; text-decoration:none; color:#465363; background:#edf2f5; border:1px solid transparent; border-bottom:none; border-radius:8px 8px 0 0; font-size:14px; }
            #rlsv-card-editor .rlsv-it a.active { color:#46aaf2; background:#fff; border-color:#d3dde3; margin-bottom:-1px; box-shadow:inset 0 3px 0 0 #46aaf2; font-weight:600; }
            /* тумблер (switch) в стиле темы */
            #rlsv-card-editor .rlsv-toggle { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid #f0f4f7; }
            #rlsv-card-editor .rlsv-toggle:last-child { border-bottom:none; }
            #rlsv-card-editor .rlsv-toggle-label { font-size:14px; color:#465363; }
            #rlsv-card-editor .rlsv-sw { position:relative; display:inline-block; width:48px; height:26px; flex:none; cursor:pointer; }
            #rlsv-card-editor .rlsv-sw input { position:absolute; opacity:0; width:0; height:0; }
            #rlsv-card-editor .rlsv-sw-track { position:absolute; top:0; left:0; right:0; bottom:0; background:#cdd9e0; border-radius:26px; transition:background .2s ease; }
            #rlsv-card-editor .rlsv-sw-thumb { position:absolute; top:3px; left:3px; width:20px; height:20px; background:#fff; border-radius:50%; box-shadow:0 1px 2px rgba(0,0,0,.3); transition:left .2s ease; }
            #rlsv-card-editor .rlsv-sw input[type="checkbox"]:checked ~ .rlsv-sw-track { background:#46aaf2; }
            #rlsv-card-editor .rlsv-sw input[type="checkbox"]:checked ~ .rlsv-sw-track .rlsv-sw-thumb { left:25px; }
        </style>

        <h3 class="ty-subheader">{__("rlsv_autopost.tab")}</h3>

        <form action="{"rlsv_account.product"|fn_url}" method="post" name="rlsv_inline_product_form">
            <input type="hidden" name="rlsv_csrf" value="{$rlsv_csrf|escape}" />
            <input type="hidden" name="product_id" value="{$product.product_id}" />
            <input type="hidden" name="rlsv_return" value="card" />

            <ul class="rlsv-it">
                <li><a href="#" data-rlsv-it="content">{__("rlsv_autopost.pub_tab_content")}</a></li>
                <li><a href="#" data-rlsv-it="pubtype">{__("rlsv_autopost.pub_tab_type")}</a></li>
            </ul>

            {* ===== Вкладка «Контент» ===== *}
            <div data-rlsv-itpane="content">
                <div class="ty-control-group">
                    <label class="ty-control-group__title" for="rlsv_inline_full_description">{__("rlsv_autopost.full_description")}</label>
                    <textarea id="rlsv_inline_full_description" name="rlsv_full_description" rows="10" class="ty-input-textarea" style="width:100%;">{$rlsv_full_description|escape}</textarea>
                    <p class="ty-control-group__description">{__("rlsv_autopost.full_description_hint")}</p>
                </div>

                <div class="ty-control-group">
                    <label class="ty-control-group__title" for="rlsv_inline_autopost">{__("rlsv_autopost.publish_product")}</label>
                    <input type="hidden" name="rlsv_autopost_product[autopost]" value="N" />
                    <input type="checkbox" id="rlsv_inline_autopost" name="rlsv_autopost_product[autopost]" value="Y"
                           {if !$p.autopost || $p.autopost == "Y"}checked="checked"{/if} />
                </div>

                <div class="ty-control-group">
                    <label class="ty-control-group__title" for="rlsv_inline_video">{__("rlsv_autopost.product_video_url")}</label>
                    <input type="text" id="rlsv_inline_video" name="rlsv_autopost_product[video_url]" value="{$p.video_url|escape}" class="ty-input-text" />
                </div>
            </div>

            {* ===== Вкладка «Тип публикации» ===== *}
            <div data-rlsv-itpane="pubtype" style="display:none;">
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

            <div class="ty-profile-field__buttons buttons-container ty-mt-s">
                <button type="submit" class="ty-btn ty-btn__primary">{__("save")}</button>
                <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.product?product_id=`$product.product_id`"|fn_url}">{__("rlsv_autopost.edit_description")}</a>
            </div>
        </form>
    </div>

    <script>
    (function(){
        function init(){
            var root = document.getElementById('rlsv-card-editor');
            if (!root) { return; }
            var tabs  = [].slice.call(root.querySelectorAll('[data-rlsv-it]'));
            var panes = [].slice.call(root.querySelectorAll('[data-rlsv-itpane]'));
            function activate(id){
                tabs.forEach(function(t){ t.className = (t.getAttribute('data-rlsv-it') === id) ? 'active' : ''; });
                panes.forEach(function(p){ p.style.display = (p.getAttribute('data-rlsv-itpane') === id) ? '' : 'none'; });
            }
            tabs.forEach(function(t){
                t.addEventListener('click', function(e){ e.preventDefault(); activate(t.getAttribute('data-rlsv-it')); });
            });
            if (tabs.length) { activate(tabs[0].getAttribute('data-rlsv-it')); }
        }
        if (document.readyState !== 'loading') { init(); }
        else { document.addEventListener('DOMContentLoaded', init); }
    })();
    </script>
{/if}
