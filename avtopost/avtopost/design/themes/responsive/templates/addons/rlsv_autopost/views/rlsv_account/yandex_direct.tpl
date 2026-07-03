{*
    Витрина: аналитика Instagram в виде «бенто»-дашборда (лента / Reels / Stories),
    в цветах темы (палитра Brightness), без внешних CDN — чистый CSS + инлайновый SVG.
    ВАЖНО: все классы имеют префикс rlsv- и НЕ содержат подстроку "span" и слово "type",
    т.к. тема (Bootstrap) стилизует [class*="span"] и .type — иначе ломается вёрстка.
    Данные: fn_rlsv_autopost_get_feature_analytics() → $rlsv_yd (agg/spark/donut в $rlsv_yd.dash).
*}
{capture name="mainbox"}
{assign var="d"  value=$rlsv_yd.dash}
{assign var="lb" value=$rlsv_yd.labels}
{assign var="fm" value=$rlsv_yd.feature_map}
<div id="rlsv-yd" class="rlsv-ap rlsv-bento-wrap">
    <style>
        #rlsv-yd.rlsv-bento-wrap { color:#465363; }
        #rlsv-yd .rlsv-bento { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:22px; }
        #rlsv-yd .rlsv-wide { grid-column:1 / -1; }
        #rlsv-yd .rlsv-sec { margin:4px 2px 10px; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#46aaf2; display:flex; align-items:center; gap:7px; }
        #rlsv-yd .rlsv-sec.rlsv-reels { color:#465363; }
        #rlsv-yd .rlsv-sec.rlsv-story { color:#f2464e; }
        #rlsv-yd .rlsv-b { background:#fff; border:1px solid #e1e9ee; border-radius:14px; padding:16px; box-shadow:0 1px 3px 0 rgba(70,83,99,.08); box-sizing:border-box; }
        #rlsv-yd .rlsv-hero { background:linear-gradient(135deg,#eef5fd 0%,#ffffff 60%); border-color:#cfe4fb; position:relative; overflow:hidden; }
        #rlsv-yd .rlsv-cap { font-size:13px; font-weight:500; color:#5b6b7d; display:flex; align-items:center; gap:8px; }
        #rlsv-yd .rlsv-num { font-size:30px; font-weight:700; color:#2b3648; line-height:1.1; letter-spacing:-.5px; margin-top:6px; }
        #rlsv-yd .rlsv-num.rlsv-sm { font-size:22px; }
        #rlsv-yd .rlsv-num.rlsv-xs { font-size:18px; }
        #rlsv-yd .rlsv-lbl { font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:#8a97a3; }
        #rlsv-yd .rlsv-ico { width:30px; height:30px; border-radius:50%; background:#e8f3fd; display:inline-flex; align-items:center; justify-content:center; flex:none; }
        #rlsv-yd .rlsv-ico svg { display:block; }
        #rlsv-yd .rlsv-head { display:flex; justify-content:space-between; align-items:flex-start; }
        #rlsv-yd .rlsv-trend { display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:700; padding:3px 8px; border-radius:9px; }
        #rlsv-yd .rlsv-trend.rlsv-up { color:#27ae60; background:rgba(39,174,96,.12); }
        #rlsv-yd .rlsv-trend.rlsv-down { color:#f2464e; background:rgba(242,70,78,.12); }
        #rlsv-yd .rlsv-split { display:flex; }
        #rlsv-yd .rlsv-split > div { flex:1; display:flex; align-items:center; justify-content:space-between; }
        #rlsv-yd .rlsv-split > div:last-child { padding-left:14px; border-left:1px solid #edf2f5; }
        #rlsv-yd .rlsv-split > div:first-child { padding-right:14px; }
        #rlsv-yd .rlsv-two { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        #rlsv-yd .rlsv-two > div + div { border-left:1px solid #edf2f5; padding-left:14px; }
        #rlsv-yd .rlsv-break { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; padding-top:12px; margin-top:12px; border-top:1px solid #edf2f5; }
        #rlsv-yd .rlsv-bkit { display:flex; flex-direction:column; align-items:center; gap:5px; }
        #rlsv-yd .rlsv-bkdot { width:9px; height:9px; border-radius:50%; }
        #rlsv-yd .rlsv-bkv { font-size:13px; font-weight:700; color:#2b3648; }
        #rlsv-yd .rlsv-rows > div { display:flex; justify-content:space-between; align-items:center; }
        #rlsv-yd .rlsv-rows > div + div { margin-top:10px; }
        #rlsv-yd .rlsv-fid { display:inline-block; margin-left:6px; padding:0 6px; border-radius:8px; background:#edf2f5; color:#46aaf2; font-size:10px; font-weight:700; vertical-align:middle; }
        #rlsv-yd .rlsv-yd-legend { border:1px dashed #d3dde3; border-radius:10px; padding:9px 12px; margin-bottom:16px; font-size:12px; color:#465363; background:#fafdff; }
        #rlsv-yd .rlsv-recent { border:1px solid #e1e9ee; border-radius:12px; padding:12px 14px; margin-bottom:12px; background:#fff; }
        #rlsv-yd .rlsv-rtype { display:inline-block; padding:2px 10px; border-radius:10px; font-size:12px; background:#e8f3fd; color:#46aaf2; }
        #rlsv-yd .rlsv-rtype.rlsv-reels { background:#fdeef0; color:#f2464e; }
        #rlsv-yd .rlsv-rtype.rlsv-story { background:#fef0dd; color:#b5670a; }
        #rlsv-yd .rlsv-rmini { display:flex; flex-wrap:wrap; gap:6px 16px; margin-top:8px; font-size:12px; color:#5b6b7d; }
        #rlsv-yd .rlsv-muted { color:#8a97a3; font-size:12px; }
        @media (max-width:480px){ #rlsv-yd .rlsv-two { grid-template-columns:1fr; } #rlsv-yd .rlsv-two > div + div { border-left:none; padding-left:0; border-top:1px solid #edf2f5; padding-top:10px; } }
    </style>

    <p class="ty-mb-s">
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.stats"|fn_url}">&larr; {__("rlsv_autopost.my_stats")}</a>
    </p>

    {if !$rlsv_yd.available}
        {if $rlsv_yd.error == "no_features"}
            <p class="ty-no-items">{__("rlsv_autopost.ig_no_features")} <a href="{"rlsv_account.settings"|fn_url}">{__("rlsv_autopost.my_settings")}</a></p>
        {elseif $rlsv_yd.error == "no_products"}
            <p class="ty-no-items">{__("rlsv_autopost.ig_no_products")}</p>
        {elseif $rlsv_yd.error == "disabled"}
            <p class="ty-no-items">{__("rlsv_autopost.ig_disabled")}</p>
        {elseif $rlsv_yd.error == "no_tokens"}
            <p class="ty-no-items">{__("rlsv_autopost.ig_no_tokens")} <a href="{"rlsv_account.settings"|fn_url}">{__("rlsv_autopost.my_settings")}</a></p>
        {else}
            <p class="ty-no-items">{__("rlsv_autopost.ig_api_error")}{if $rlsv_yd.error}: {$rlsv_yd.error|escape}{/if}</p>
        {/if}
    {else}
        <p class="rlsv-muted ty-mb-s">{__("rlsv_autopost.ig_features_note")}</p>

        {if $fm}
            <div class="rlsv-yd-legend">
                <strong>{__("rlsv_autopost.ig_bindings")}:</strong>
                {foreach from=$fm key="mk" item="fid" name="fm"}
                    {if $lb[$mk]}{$lb[$mk]|escape}{else}{$mk|escape}{/if} &rarr; #{$fid}{if !$smarty.foreach.fm.last}; {/if}
                {/foreach}
            </div>
        {/if}

                {* ===================== БАЗОВЫЕ МЕТРИКИ (YANDEX DIRECT) ===================== *}
        <div class="rlsv-sec">БАЗОВЫЕ МЕТРИКИ</div>
        <div class="rlsv-bento">

            <div class="rlsv-b rlsv-hero rlsv-wide">
                <div class="rlsv-head">
                    <span class="rlsv-cap">
                        <span class="rlsv-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#46aaf2" stroke-width="2"><circle cx="9" cy="7" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 4.5a3 3 0 0 1 0 5.5"/><path d="M18.5 20a6 6 0 0 0-3.2-5.3"/></svg></span>
                        {$lb.impressions|escape}{if $fm.impressions}<span class="rlsv-fid">#{$fm.impressions}</span>{/if}
                    </span>
                </div>
                <div class="rlsv-num">{$d.agg.feed.impressions|number_format:0:".":" "}</div>
            </div>

            <div class="rlsv-b">
                <div class="rlsv-head"><span class="rlsv-lbl">{$lb.clicks|escape}{if $fm.clicks}<span class="rlsv-fid">#{$fm.clicks}</span>{/if}</span></div>
                <div class="rlsv-num rlsv-sm">{$d.agg.feed.clicks|number_format:0:".":" "}</div>
            </div>

            <div class="rlsv-b">
                <div class="rlsv-head"><span class="rlsv-lbl">{$lb.cost|escape}{if $fm.cost}<span class="rlsv-fid">#{$fm.cost}</span>{/if}</span></div>
                <div class="rlsv-num rlsv-sm">{$d.agg.feed.cost|number_format:0:".":" "}</div>
            </div>

            <div class="rlsv-b rlsv-wide rlsv-split">
                <div>
                    <div><span class="rlsv-lbl">{$lb.conversions|escape}{if $fm.conversions}<span class="rlsv-fid">#{$fm.conversions}</span>{/if}</span><div class="rlsv-num rlsv-xs">{$d.agg.feed.conversions|number_format:0:".":" "}</div></div>
                </div>
                <div>
                    <div><span class="rlsv-lbl">{$lb.revenue|escape}{if $fm.revenue}<span class="rlsv-fid">#{$fm.revenue}</span>{/if}</span><div class="rlsv-num rlsv-xs">{$d.agg.feed.revenue|number_format:0:".":" "}</div></div>
                </div>
            </div>

            <div class="rlsv-b">
                <div class="rlsv-head"><span class="rlsv-lbl">{$lb.roi|escape}{if $fm.roi}<span class="rlsv-fid">#{$fm.roi}</span>{/if}</span></div>
                <div class="rlsv-num rlsv-sm">{$d.agg.feed.roi|number_format:0:".":" "}</div>
            </div>

            <div class="rlsv-b">
                <div class="rlsv-head"><span class="rlsv-lbl">{$lb.cpa|escape}{if $fm.cpa}<span class="rlsv-fid">#{$fm.cpa}</span>{/if}</span></div>
                <div class="rlsv-num rlsv-sm">{$d.agg.feed.cpa|number_format:0:".":" "}</div>
            </div>

        </div>

        {* ===================== ПО ТОВАРАМ (детализация) ===================== *}
        {if $rlsv_yd.media}
            <div class="rlsv-sec">{__("rlsv_autopost.tab_by_product")}</div>
            {foreach from=$rlsv_yd.media item="m"}
                <div class="rlsv-recent">
                    <span class="rlsv-rtype {if $m.type_key == 'REELS'}rlsv-reels{elseif $m.type_key == 'STORY'}rlsv-story{/if}">{$m.type_label|escape}</span>
                    {if $m.product_id}<span class="rlsv-muted"> &nbsp;{__("rlsv_autopost.ig_product")}: <a href="{"products.view?product_id=`$m.product_id`"|fn_url}" target="_blank" rel="noopener">#{$m.product_id}</a></span>{/if}
                    {if $m.metrics}
                        <div class="rlsv-rmini">
                            {foreach from=$m.metrics key="mk" item="mv"}
                                <span>{if $lb[$mk]}{$lb[$mk]|escape}{else}{$mk|escape}{/if}: <strong>{$mv|number_format:0:".":" "}</strong>{if $fm[$mk]} <span class="rlsv-fid">#{$fm[$mk]}</span>{/if}</span>
                            {/foreach}
                        </div>
                    {/if}
                </div>
            {/foreach}
        {/if}
    {/if}
</div>
{/capture}

{$smarty.capture.mainbox nofilter}
{capture name="mainbox_title"}{__("rlsv_autopost.yandex_direct_analytics")}{/capture}
