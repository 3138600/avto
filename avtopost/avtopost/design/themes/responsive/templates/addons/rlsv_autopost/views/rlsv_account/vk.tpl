{*
    Витрина: аналитика Instagram в виде «бенто»-дашборда (лента / Reels / Stories),
    в цветах темы (палитра Brightness), без внешних CDN — чистый CSS + инлайновый SVG.
    ВАЖНО: все классы имеют префикс rlsv- и НЕ содержат подстроку "span" и слово "type",
    т.к. тема (Bootstrap) стилизует [class*="span"] и .type — иначе ломается вёрстка.
    Данные: fn_rlsv_autopost_get_feature_analytics() → $rlsv_vk (agg/spark/donut в $rlsv_vk.dash).
*}
{capture name="mainbox"}
{assign var="d"  value=$rlsv_vk.dash}
{assign var="lb" value=$rlsv_vk.labels}
{assign var="fm" value=$rlsv_vk.feature_map}
<div id="rlsv-vk" class="rlsv-ap rlsv-bento-wrap">
    <style>
        #rlsv-vk.rlsv-bento-wrap { color:#465363; }
        #rlsv-vk .rlsv-bento { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:22px; }
        #rlsv-vk .rlsv-wide { grid-column:1 / -1; }
        #rlsv-vk .rlsv-sec { margin:4px 2px 10px; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#46aaf2; display:flex; align-items:center; gap:7px; }
        #rlsv-vk .rlsv-sec.rlsv-reels { color:#465363; }
        #rlsv-vk .rlsv-sec.rlsv-story { color:#f2464e; }
        #rlsv-vk .rlsv-b { background:#fff; border:1px solid #e1e9ee; border-radius:14px; padding:16px; box-shadow:0 1px 3px 0 rgba(70,83,99,.08); box-sizing:border-box; }
        #rlsv-vk .rlsv-hero { background:linear-gradient(135deg,#eef5fd 0%,#ffffff 60%); border-color:#cfe4fb; position:relative; overflow:hidden; }
        #rlsv-vk .rlsv-cap { font-size:13px; font-weight:500; color:#5b6b7d; display:flex; align-items:center; gap:8px; }
        #rlsv-vk .rlsv-num { font-size:30px; font-weight:700; color:#2b3648; line-height:1.1; letter-spacing:-.5px; margin-top:6px; }
        #rlsv-vk .rlsv-num.rlsv-sm { font-size:22px; }
        #rlsv-vk .rlsv-num.rlsv-xs { font-size:18px; }
        #rlsv-vk .rlsv-lbl { font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:#8a97a3; }
        #rlsv-vk .rlsv-ico { width:30px; height:30px; border-radius:50%; background:#e8f3fd; display:inline-flex; align-items:center; justify-content:center; flex:none; }
        #rlsv-vk .rlsv-ico svg { display:block; }
        #rlsv-vk .rlsv-head { display:flex; justify-content:space-between; align-items:flex-start; }
        #rlsv-vk .rlsv-trend { display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:700; padding:3px 8px; border-radius:9px; }
        #rlsv-vk .rlsv-trend.rlsv-up { color:#27ae60; background:rgba(39,174,96,.12); }
        #rlsv-vk .rlsv-trend.rlsv-down { color:#f2464e; background:rgba(242,70,78,.12); }
        #rlsv-vk .rlsv-split { display:flex; }
        #rlsv-vk .rlsv-split > div { flex:1; display:flex; align-items:center; justify-content:space-between; }
        #rlsv-vk .rlsv-split > div:last-child { padding-left:14px; border-left:1px solid #edf2f5; }
        #rlsv-vk .rlsv-split > div:first-child { padding-right:14px; }
        #rlsv-vk .rlsv-two { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        #rlsv-vk .rlsv-two > div + div { border-left:1px solid #edf2f5; padding-left:14px; }
        #rlsv-vk .rlsv-break { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; padding-top:12px; margin-top:12px; border-top:1px solid #edf2f5; }
        #rlsv-vk .rlsv-bkit { display:flex; flex-direction:column; align-items:center; gap:5px; }
        #rlsv-vk .rlsv-bkdot { width:9px; height:9px; border-radius:50%; }
        #rlsv-vk .rlsv-bkv { font-size:13px; font-weight:700; color:#2b3648; }
        #rlsv-vk .rlsv-rows > div { display:flex; justify-content:space-between; align-items:center; }
        #rlsv-vk .rlsv-rows > div + div { margin-top:10px; }
        #rlsv-vk .rlsv-fid { display:inline-block; margin-left:6px; padding:0 6px; border-radius:8px; background:#edf2f5; color:#46aaf2; font-size:10px; font-weight:700; vertical-align:middle; }
        #rlsv-vk .rlsv-vk-legend { border:1px dashed #d3dde3; border-radius:10px; padding:9px 12px; margin-bottom:16px; font-size:12px; color:#465363; background:#fafdff; }
        #rlsv-vk .rlsv-recent { border:1px solid #e1e9ee; border-radius:12px; padding:12px 14px; margin-bottom:12px; background:#fff; }
        #rlsv-vk .rlsv-rtype { display:inline-block; padding:2px 10px; border-radius:10px; font-size:12px; background:#e8f3fd; color:#46aaf2; }
        #rlsv-vk .rlsv-rtype.rlsv-reels { background:#fdeef0; color:#f2464e; }
        #rlsv-vk .rlsv-rtype.rlsv-story { background:#fef0dd; color:#b5670a; }
        #rlsv-vk .rlsv-rmini { display:flex; flex-wrap:wrap; gap:6px 16px; margin-top:8px; font-size:12px; color:#5b6b7d; }
        #rlsv-vk .rlsv-muted { color:#8a97a3; font-size:12px; }
        @media (max-width:480px){ #rlsv-vk .rlsv-two { grid-template-columns:1fr; } #rlsv-vk .rlsv-two > div + div { border-left:none; padding-left:0; border-top:1px solid #edf2f5; padding-top:10px; } }
    </style>

    <p class="ty-mb-s">
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.stats"|fn_url}">&larr; {__("rlsv_autopost.my_stats")}</a>
    </p>

    {if !$rlsv_vk.available}
        {if $rlsv_vk.error == "no_features"}
            <p class="ty-no-items">{__("rlsv_autopost.ig_no_features")} <a href="{"rlsv_account.settings"|fn_url}">{__("rlsv_autopost.my_settings")}</a></p>
        {elseif $rlsv_vk.error == "no_products"}
            <p class="ty-no-items">{__("rlsv_autopost.ig_no_products")}</p>
        {elseif $rlsv_vk.error == "disabled"}
            <p class="ty-no-items">{__("rlsv_autopost.ig_disabled")}</p>
        {elseif $rlsv_vk.error == "no_tokens"}
            <p class="ty-no-items">{__("rlsv_autopost.ig_no_tokens")} <a href="{"rlsv_account.settings"|fn_url}">{__("rlsv_autopost.my_settings")}</a></p>
        {else}
            <p class="ty-no-items">{__("rlsv_autopost.ig_api_error")}{if $rlsv_vk.error}: {$rlsv_vk.error|escape}{/if}</p>
        {/if}
    {else}
        <p class="rlsv-muted ty-mb-s">{__("rlsv_autopost.ig_features_note")}</p>

        {if $fm}
            <div class="rlsv-vk-legend">
                <strong>{__("rlsv_autopost.ig_bindings")}:</strong>
                {foreach from=$fm key="mk" item="fid" name="fm"}
                    {if $lb[$mk]}{$lb[$mk]|escape}{else}{$mk|escape}{/if} &rarr; #{$fid}{if !$smarty.foreach.fm.last}; {/if}
                {/foreach}
            </div>
        {/if}

        {* ===================== БАЗОВЫЕ МЕТРИКИ (ПОСТЫ) ===================== *}
        <div class="rlsv-sec">{__("rlsv_autopost.ig_sec_basic")}</div>
        <div class="rlsv-bento">

            <div class="rlsv-b rlsv-hero rlsv-wide">
                <div class="rlsv-head">
                    <span class="rlsv-cap">
                        <span class="rlsv-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#46aaf2" stroke-width="2"><circle cx="9" cy="7" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 4.5a3 3 0 0 1 0 5.5"/><path d="M18.5 20a6 6 0 0 0-3.2-5.3"/></svg></span>
                        {$lb.reach|escape}{if $fm.reach}<span class="rlsv-fid">#{$fm.reach}</span>{/if}
                    </span>
                    {if $d.trend !== null}
                        {if $d.trend >= 0}<span class="rlsv-trend rlsv-up">&#9650; +{$d.trend}%</span>
                        {else}<span class="rlsv-trend rlsv-down">&#9660; {$d.trend}%</span>{/if}
                    {/if}
                </div>
                <div class="rlsv-num">{$d.agg.feed.reach|number_format:0:".":" "}</div>
                {if $d.spark.has}
                    <svg viewBox="0 0 {$d.spark.w} {$d.spark.h}" preserveAspectRatio="none" style="width:100%;height:56px;margin-top:8px;display:block;">
                        <defs><linearGradient id="rlsvSpark" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#46aaf2" stop-opacity="0.28"/>
                            <stop offset="100%" stop-color="#46aaf2" stop-opacity="0"/>
                        </linearGradient></defs>
                        <path d="{$d.spark.area}" fill="url(#rlsvSpark)"/>
                        <polyline points="{$d.spark.points}" fill="none" stroke="#46aaf2" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                {/if}
            </div>

            <div class="rlsv-b">
                <div class="rlsv-head"><span class="rlsv-lbl">{$lb.impressions|escape}{if $fm.impressions}<span class="rlsv-fid">#{$fm.impressions}</span>{/if}</span></div>
                <div class="rlsv-num rlsv-sm">{$d.agg.feed.impressions|number_format:0:".":" "}</div>
            </div>

            <div class="rlsv-b">
                <div class="rlsv-head"><span class="rlsv-lbl">{$lb.engagement|escape}{if $fm.engagement}<span class="rlsv-fid">#{$fm.engagement}</span>{/if}</span></div>
                <div class="rlsv-num rlsv-sm">{$d.agg.feed.engagement|number_format:0:".":" "}</div>
            </div>

            <div class="rlsv-b rlsv-wide rlsv-split">
                <div>
                    <div><span class="rlsv-lbl">{$lb.saved|escape}{if $fm.saved}<span class="rlsv-fid">#{$fm.saved}</span>{/if}</span><div class="rlsv-num rlsv-xs">{$d.agg.feed.saved|number_format:0:".":" "}</div></div>
                </div>
                <div>
                    <div><span class="rlsv-lbl">{$lb.video_views|escape}{if $fm.video_views}<span class="rlsv-fid">#{$fm.video_views}</span>{/if}</span><div class="rlsv-num rlsv-xs">{$d.agg.feed.video_views|number_format:0:".":" "}</div></div>
                </div>
            </div>
        </div>

        {* ===================== REELS ===================== *}
        <div class="rlsv-sec rlsv-reels">{__("rlsv_autopost.ig_type_reels")}</div>
        <div class="rlsv-bento">
            <div class="rlsv-b rlsv-wide rlsv-two">
                <div>
                    <span class="rlsv-cap">{$lb.plays|escape}{if $fm.plays}<span class="rlsv-fid">#{$fm.plays}</span>{/if}</span>
                    <div class="rlsv-num rlsv-sm">{$d.agg.reels.plays|number_format:0:".":" "}</div>
                </div>
                <div>
                    <span class="rlsv-cap">{$lb.reach|escape}{if $fm.reach}<span class="rlsv-fid">#{$fm.reach}</span>{/if}</span>
                    <div class="rlsv-num rlsv-sm">{$d.agg.reels.reach|number_format:0:".":" "}</div>
                </div>
            </div>

            <div class="rlsv-b rlsv-wide">
                <div class="rlsv-head">
                    <div>
                        <span class="rlsv-cap">{$lb.total_interactions|escape}{if $fm.total_interactions}<span class="rlsv-fid">#{$fm.total_interactions}</span>{/if}</span>
                        <div class="rlsv-num rlsv-sm">{$d.agg.reels.total_interactions|number_format:0:".":" "}</div>
                    </div>
                    {if $d.donut.total > 0}
                        <svg width="{$d.donut.size}" height="{$d.donut.size}" viewBox="0 0 {$d.donut.size} {$d.donut.size}">
                            <g transform="rotate(-90 {$d.donut.c} {$d.donut.c})">
                                <circle cx="{$d.donut.c}" cy="{$d.donut.c}" r="{$d.donut.r}" fill="none" stroke="#edf2f5" stroke-width="{$d.donut.sw}"/>
                                {foreach from=$d.donut.segments item="s"}
                                    <circle cx="{$d.donut.c}" cy="{$d.donut.c}" r="{$d.donut.r}" fill="none" stroke="{$s.color}" stroke-width="{$d.donut.sw}" stroke-dasharray="{$s.dasharray}" stroke-dashoffset="{$s.dashoffset}" stroke-linecap="butt"/>
                                {/foreach}
                            </g>
                        </svg>
                    {/if}
                </div>
                <div class="rlsv-break">
                    <div class="rlsv-bkit"><span class="rlsv-bkdot" style="background:#f2464e;"></span><span class="rlsv-bkv">{$d.agg.reels.likes|number_format:0:".":" "}</span><span class="rlsv-lbl">{$lb.likes|escape}</span></div>
                    <div class="rlsv-bkit"><span class="rlsv-bkdot" style="background:#46aaf2;"></span><span class="rlsv-bkv">{$d.agg.reels.comments|number_format:0:".":" "}</span><span class="rlsv-lbl">{$lb.comments|escape}</span></div>
                    <div class="rlsv-bkit"><span class="rlsv-bkdot" style="background:#27ae60;"></span><span class="rlsv-bkv">{$d.agg.reels.saves|number_format:0:".":" "}</span><span class="rlsv-lbl">{$lb.saves|escape}</span></div>
                    <div class="rlsv-bkit"><span class="rlsv-bkdot" style="background:#8a97a3;"></span><span class="rlsv-bkv">{$d.agg.reels.shares|number_format:0:".":" "}</span><span class="rlsv-lbl">{$lb.shares|escape}</span></div>
                </div>
            </div>
        </div>

        {* ===================== STORIES ===================== *}
        <div class="rlsv-sec rlsv-story">{__("rlsv_autopost.ig_sec_stories")}</div>
        <div class="rlsv-bento">
            <div class="rlsv-b">
                <div><span class="rlsv-lbl">{$lb.impressions|escape}</span><div class="rlsv-num rlsv-xs">{$d.agg.stories.impressions|number_format:0:".":" "}</div></div>
                <div style="height:1px;background:#edf2f5;margin:12px 0;"></div>
                <div><span class="rlsv-lbl">{$lb.reach|escape}</span><div class="rlsv-num rlsv-xs">{$d.agg.stories.reach|number_format:0:".":" "}</div></div>
            </div>

            <div class="rlsv-b rlsv-rows">
                <div><span class="rlsv-lbl">{$lb.taps_forward|escape}{if $fm.taps_forward}<span class="rlsv-fid">#{$fm.taps_forward}</span>{/if}</span><span class="rlsv-num rlsv-xs">{$d.agg.stories.taps_forward|number_format:0:".":" "}</span></div>
                <div><span class="rlsv-lbl">{$lb.taps_back|escape}{if $fm.taps_back}<span class="rlsv-fid">#{$fm.taps_back}</span>{/if}</span><span class="rlsv-num rlsv-xs">{$d.agg.stories.taps_back|number_format:0:".":" "}</span></div>
                <div><span class="rlsv-lbl">{$lb.exits|escape}{if $fm.exits}<span class="rlsv-fid">#{$fm.exits}</span>{/if}</span><span class="rlsv-num rlsv-xs" style="color:#f2464e;">{$d.agg.stories.exits|number_format:0:".":" "}</span></div>
            </div>

            <div class="rlsv-b rlsv-wide" style="display:flex;justify-content:space-between;align-items:center;">
                <span class="rlsv-cap">
                    <span class="rlsv-ico" style="background:#eef1fd;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#46aaf2" stroke-width="2"><path d="M9 17l-5-5 5-5"/><path d="M4 12h11a5 5 0 0 1 5 5v1"/></svg></span>
                    {$lb.replies|escape}{if $fm.replies}<span class="rlsv-fid">#{$fm.replies}</span>{/if}
                </span>
                <span class="rlsv-num rlsv-sm">{$d.agg.stories.replies|number_format:0:".":" "}</span>
            </div>
        </div>

        {* ===================== ПО ТОВАРАМ (детализация) ===================== *}
        {if $rlsv_vk.media}
            <div class="rlsv-sec">{__("rlsv_autopost.tab_by_product")}</div>
            {foreach from=$rlsv_vk.media item="m"}
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
{capture name="mainbox_title"}{__("rlsv_autopost.vk_analytics")}{/capture}
