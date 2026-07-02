{*
    Витрина: аналитика публикаций пользователя.
    dispatch=rlsv_account.stats
    Вкладки: Обзор + по каждому каналу + по товарам. На «Обзоре» и на вкладках
    площадок — карточки KPI и пончиковые графики (в цветах темы Brightness).
    Данные ограничены текущим пользователем в контроллере; вывод экранируется.
*}
{capture name="mainbox"}
<div id="rlsv-ap-stats" class="rlsv-ap">

    <style>
        /* Палитра Brightness: base #edf2f5, font #465363, primary(red) #f2464e,
           secondary(blue) #46aaf2, in_stock #27ae60, out_of_stock #a80006, discount #fc9432 */
        .rlsv-ap .rlsv-tabs { list-style:none; margin:0 0 16px; padding:0; border-bottom:1px solid #d3dde3; display:flex; flex-wrap:wrap; }
        .rlsv-ap .rlsv-tabs li { margin:0 4px 0 0; }
        .rlsv-ap .rlsv-tabs a { display:block; padding:10px 18px; text-decoration:none; color:#465363; background:#edf2f5; border:1px solid transparent; border-bottom:none; border-radius:5px 5px 0 0; }
        .rlsv-ap .rlsv-tabs a:hover { background:#fff; color:#3182c4; }
        .rlsv-ap .rlsv-tabs a.active { color:#46aaf2; background:#fff; border-color:#d3dde3; margin-bottom:-1px; box-shadow:inset 0 3px 0 0 #46aaf2; font-weight:600; }
        .rlsv-ap .rlsv-cards { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .rlsv-ap .rlsv-card { flex:1 1 110px; background:#fff; border:1px solid #e1e9ee; border-radius:12px; padding:14px; text-align:center; box-shadow:0 1px 3px 0 rgba(70,83,99,.08); }
        .rlsv-ap .rlsv-card .num { font-size:26px; font-weight:700; line-height:1; color:#2b3648; letter-spacing:-.5px; }
        .rlsv-ap .rlsv-card .lbl { color:#8a97a3; font-size:11px; margin-top:5px; text-transform:uppercase; letter-spacing:.05em; }
        .rlsv-ap .rlsv-card.ok .num { color:#27ae60; }
        .rlsv-ap .rlsv-card.err .num { color:#a80006; }
        .rlsv-ap .rlsv-card.rate .num { color:#46aaf2; }
        .rlsv-ap .rlsv-charts { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
        .rlsv-ap .rlsv-chart { flex:1 1 260px; background:#fff; border:1px solid #e1e9ee; border-radius:14px; padding:16px; box-shadow:0 1px 3px 0 rgba(70,83,99,.08); }
        .rlsv-ap .rlsv-chart-t { font-size:11px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:#5b6b7d; margin-bottom:12px; }
        .rlsv-ap .rlsv-donut-row { display:flex; align-items:center; gap:18px; flex-wrap:wrap; }
        .rlsv-ap .rlsv-donut { flex:none; }
        .rlsv-ap .rlsv-donut svg { display:block; }
        .rlsv-ap .rlsv-legend { flex:1 1 150px; }
        .rlsv-ap .rlsv-legend-row { display:flex; align-items:center; gap:8px; margin:5px 0; font-size:13px; }
        .rlsv-ap .rlsv-legend-row .dot { width:10px; height:10px; border-radius:50%; flex:none; }
        .rlsv-ap .rlsv-legend-row .l { color:#5b6b7d; flex:1; }
        .rlsv-ap .rlsv-legend-row .v { font-weight:700; color:#2b3648; }
        .rlsv-ap .rlsv-muted { color:#8a97a3; font-size:12px; }
        .rlsv-ap .rlsv-badge { display:inline-block; padding:1px 8px; border-radius:10px; font-size:11px; }
        .rlsv-ap .rlsv-badge.done { background:#e4f5ea; color:#27ae60; }
        .rlsv-ap .rlsv-badge.error { background:#f7e1e1; color:#a80006; }
        .rlsv-ap .rlsv-badge.pending,.rlsv-ap .rlsv-badge.processing { background:#fef0dd; color:#b5670a; }
    </style>

    {* ----- Навигация по вкладкам ----- *}

    <p class="ty-mb-s">
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.instagram"|fn_url}">{__("rlsv_autopost.ig_analytics")}</a>
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.vk"|fn_url}">{__("rlsv_autopost.vk_analytics")}</a>
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.telegram"|fn_url}">{__("rlsv_autopost.telegram_analytics")}</a>
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.ok"|fn_url}">{__("rlsv_autopost.ok_analytics")}</a>
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.pinterest"|fn_url}">{__("rlsv_autopost.pinterest_analytics")}</a>
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.max"|fn_url}">{__("rlsv_autopost.max_analytics")}</a>
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.youtube"|fn_url}">{__("rlsv_autopost.youtube_analytics")}</a>
        <a class="ty-btn ty-btn__secondary" href="{"rlsv_account.rutube"|fn_url}">{__("rlsv_autopost.rutube_analytics")}</a>
    </p>
    <ul class="rlsv-tabs">
        <li><a href="#" data-rlsv-tab="overview">{__("rlsv_autopost.tab_overview")}</a></li>
        {foreach from=$rlsv_platforms item="pf"}
            <li><a href="#" data-rlsv-tab="ch_{$pf|escape}">{$pf|escape} ({$rlsv_by_channel.$pf.total|default:0})</a></li>
        {/foreach}
        <li><a href="#" data-rlsv-tab="by_product">{__("rlsv_autopost.tab_by_product")}</a></li>
    </ul>

    {* ===== Обзор ===== *}
    <div data-rlsv-pane="overview">
        <div class="rlsv-cards">
            <div class="rlsv-card"><div class="num">{$rlsv_ov_dash.total|number_format:0:".":" "}</div><div class="lbl">{__("rlsv_autopost.stat_total")}</div></div>
            <div class="rlsv-card ok"><div class="num">{$rlsv_ov_dash.done|number_format:0:".":" "}</div><div class="lbl">{__("rlsv_autopost.stat_done")}</div></div>
            <div class="rlsv-card err"><div class="num">{$rlsv_ov_dash.error|number_format:0:".":" "}</div><div class="lbl">{__("rlsv_autopost.stat_error")}</div></div>
            <div class="rlsv-card"><div class="num">{$rlsv_ov_dash.pending|number_format:0:".":" "}</div><div class="lbl">{__("rlsv_autopost.stat_pending")}</div></div>
            <div class="rlsv-card rate"><div class="num">{$rlsv_ov_dash.success}%</div><div class="lbl">{__("rlsv_autopost.stat_success")}</div></div>
        </div>

        <div class="rlsv-charts">
            <div class="rlsv-chart">
                <div class="rlsv-chart-t">{__("rlsv_autopost.chart_status")}</div>
                {include file="addons/rlsv_autopost/views/rlsv_account/_chart.tpl" chart=$rlsv_ov_dash.status}
            </div>
            <div class="rlsv-chart">
                <div class="rlsv-chart-t">{__("rlsv_autopost.chart_platforms")}</div>
                {include file="addons/rlsv_autopost/views/rlsv_account/_chart.tpl" chart=$rlsv_ov_dash.platform}
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table width="100%" class="ty-table">
                <thead><tr>
                    <th>{__("rlsv_autopost.col_product")}</th>
                    <th>{__("rlsv_autopost.col_platform")}</th>
                    <th>{__("rlsv_autopost.col_status")}</th>
                    <th>{__("rlsv_autopost.col_date")}</th>
                    <th>{__("rlsv_autopost.col_error")}</th>
                </tr></thead>
                <tbody>
                    {foreach from=$rlsv_stats.rows item="row"}
                        <tr>
                            <td>{$row.product_name|escape}</td>
                            <td>{$row.platform|escape}</td>
                            <td><span class="rlsv-badge {$row.status|escape}">{$row.status|escape}</span></td>
                            <td>{if $row.processed_at}{$row.processed_at|date_format:"%d.%m.%Y %H:%M"}{else}&mdash;{/if}</td>
                            <td>{$row.error|escape|truncate:120}</td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="5">{__("no_data")}</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    {* ===== По каналам ===== *}
    {foreach from=$rlsv_platforms item="pf"}
        {assign var="ch" value=$rlsv_by_channel.$pf}
        {assign var="cd" value=$rlsv_ch_dash.$pf}
        <div data-rlsv-pane="ch_{$pf|escape}" style="display:none;">
            <div class="rlsv-cards">
                <div class="rlsv-card"><div class="num">{$cd.total|number_format:0:".":" "}</div><div class="lbl">{__("rlsv_autopost.stat_total")}</div></div>
                <div class="rlsv-card ok"><div class="num">{$cd.done|number_format:0:".":" "}</div><div class="lbl">{__("rlsv_autopost.stat_done")}</div></div>
                <div class="rlsv-card err"><div class="num">{$cd.error|number_format:0:".":" "}</div><div class="lbl">{__("rlsv_autopost.stat_error")}</div></div>
                <div class="rlsv-card"><div class="num">{$cd.pending|number_format:0:".":" "}</div><div class="lbl">{__("rlsv_autopost.stat_pending")}</div></div>
                <div class="rlsv-card rate"><div class="num">{$cd.success}%</div><div class="lbl">{__("rlsv_autopost.stat_success")}</div></div>
            </div>

            <div class="rlsv-charts">
                <div class="rlsv-chart">
                    <div class="rlsv-chart-t">{__("rlsv_autopost.chart_status")} &mdash; {$pf|escape}</div>
                    {include file="addons/rlsv_autopost/views/rlsv_account/_chart.tpl" chart=$cd.status}
                </div>
            </div>

            <div class="table-responsive-wrapper">
                <table width="100%" class="ty-table">
                    <thead><tr>
                        <th>{__("rlsv_autopost.col_product")}</th>
                        <th>{__("rlsv_autopost.col_status")}</th>
                        <th>{__("rlsv_autopost.col_date")}</th>
                        <th>{__("rlsv_autopost.col_error")}</th>
                    </tr></thead>
                    <tbody>
                        {foreach from=$ch.rows item="row"}
                            <tr>
                                <td>{if $row.product_name}{$row.product_name|escape}{else}#{$row.product_id}{/if}</td>
                                <td><span class="rlsv-badge {$row.status|escape}">{$row.status|escape}</span></td>
                                <td>{if $row.processed_at}{$row.processed_at|date_format:"%d.%m.%Y %H:%M"}{else}&mdash;{/if}</td>
                                <td>{$row.error|escape|truncate:120}</td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="4">{__("no_data")}</td></tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {/foreach}

    {* ===== По товарам ===== *}
    <div data-rlsv-pane="by_product" style="display:none;">
        <div class="table-responsive-wrapper">
            <table width="100%" class="ty-table">
                <thead><tr>
                    <th>{__("rlsv_autopost.col_product")}</th>
                    <th>{__("rlsv_autopost.stat_total")}</th>
                    <th>{__("rlsv_autopost.stat_done")}</th>
                    <th>{__("rlsv_autopost.stat_error")}</th>
                    <th>{__("rlsv_autopost.last_published")}</th>
                    <th>{__("rlsv_autopost.stat_by_platform")}</th>
                    <th>&nbsp;</th>
                </tr></thead>
                <tbody>
                    {foreach from=$rlsv_by_product item="p"}
                        <tr>
                            <td><a href="{$p.url}">{$p.product_name|escape}</a></td>
                            <td>{$p.total}</td>
                            <td>{$p.done}</td>
                            <td>{$p.err}</td>
                            <td>{if $p.last_at}{$p.last_at|date_format:"%d.%m.%Y %H:%M"}{else}&mdash;{/if}</td>
                            <td>
                                {foreach from=$p.platforms key="pf" item="pp" name="pp"}
                                    {$pf|escape}: {$pp.done}/{$pp.cnt}{if !$smarty.foreach.pp.last}; {/if}
                                {/foreach}
                            </td>
                            <td><a class="ty-btn ty-btn__secondary" href="{$p.edit_url}">{__("rlsv_autopost.edit_description")}</a></td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="7">{__("no_data")}</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    <script>
    (function(){
        function init(){
            var root = document.getElementById('rlsv-ap-stats');
            if (!root) { return; }
            var tabs  = [].slice.call(root.querySelectorAll('[data-rlsv-tab]'));
            var panes = [].slice.call(root.querySelectorAll('[data-rlsv-pane]'));
            function activate(id){
                tabs.forEach(function(t){
                    t.className = (t.getAttribute('data-rlsv-tab') === id) ? 'active' : '';
                });
                panes.forEach(function(p){
                    p.style.display = (p.getAttribute('data-rlsv-pane') === id) ? '' : 'none';
                });
            }
            tabs.forEach(function(t){
                t.addEventListener('click', function(e){
                    e.preventDefault();
                    activate(t.getAttribute('data-rlsv-tab'));
                });
            });
            if (tabs.length) { activate(tabs[0].getAttribute('data-rlsv-tab')); }
        }
        if (document.readyState !== 'loading') { init(); }
        else { document.addEventListener('DOMContentLoaded', init); }
    })();
    </script>

</div>
{/capture}

{$smarty.capture.mainbox nofilter}
{capture name="mainbox_title"}{__("rlsv_autopost.my_stats")}{/capture}
