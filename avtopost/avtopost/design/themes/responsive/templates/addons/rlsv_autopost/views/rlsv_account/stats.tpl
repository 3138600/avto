{*
    Витрина: аналитика публикаций пользователя.
    dispatch=rlsv_account.stats
*}
{capture name="mainbox"}
<div id="rlsv-ap-stats" class="rlsv-ap">

<!-- Tailwind CSS for rapid styling -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Google Fonts: Inter for clean numbers and UI -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- FontAwesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
    tailwind.config = {
        corePlugins: {
            preflight: false,
        },
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                },
                colors: {
                    slate: {
                        850: '#151f32',
                        900: '#0f172a',
                        950: '#020617',
                    },
                    brand: {
                        DEFAULT: '#46aaf2',
                        light: '#72c2ff',
                        dark: '#3182c4'
                    },
                    success: '#27ae60',
                    danger: '#a80006',
                    warning: '#fc9432'
                }
            }
        }
    }
</script>

<style>
    /* Custom scrollbar hiding for clean UI */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .bento-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .bento-card:active { transform: scale(0.98); }
</style>

<div class="tw-scope w-full bg-slate-50 min-h-screen relative overflow-hidden flex flex-col font-sans">

    {* ----- Навигация по вкладкам ----- *}
    <div class="px-5 py-4 border-b border-slate-200 bg-white sticky top-0 z-40 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold text-slate-900 m-0">{__("rlsv_autopost.my_stats")}</h1>
            <a href="{"rlsv_account.instagram"|fn_url}" class="bg-brand/10 hover:bg-brand/20 text-brand font-medium py-1.5 px-4 rounded-full transition-colors text-sm shadow-sm" style="text-decoration: none;">
                <i class="fa-brands fa-instagram mr-1"></i> {__("rlsv_autopost.ig_analytics")}
            </a>
        </div>

        <ul class="flex flex-wrap gap-2 m-0 p-0 list-none rlsv-tabs">
            <li>
                <a href="#" data-rlsv-tab="overview" class="block px-4 py-2 text-sm font-medium rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 transition-colors" style="text-decoration: none;">
                    {__("rlsv_autopost.tab_overview")}
                </a>
            </li>
            {foreach from=$rlsv_platforms item="pf"}
                <li>
                    <a href="#" data-rlsv-tab="ch_{$pf|escape}" class="block px-4 py-2 text-sm font-medium rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 transition-colors capitalize" style="text-decoration: none;">
                        {$pf|escape} <span class="bg-slate-200 text-slate-700 rounded-full px-2 py-0.5 ml-1 text-xs">{$rlsv_by_channel.$pf.total|default:0}</span>
                    </a>
                </li>
            {/foreach}
            <li>
                <a href="#" data-rlsv-tab="by_product" class="block px-4 py-2 text-sm font-medium rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 transition-colors" style="text-decoration: none;">
                    {__("rlsv_autopost.tab_by_product")}
                </a>
            </li>
        </ul>
        <style>
            .rlsv-tabs a.active { background-color: #46aaf2; color: #fff; box-shadow: 0 2px 4px rgba(70,170,242,0.3); }
            .rlsv-tabs a.active:hover { background-color: #3182c4; color: #fff; }
            .rlsv-tabs a.active span { background-color: rgba(255,255,255,0.2); color:#fff; }
        </style>
    </div>

    <main class="flex-1 overflow-y-auto no-scrollbar p-5 pb-24">
        {* ===== Обзор ===== *}
        <div data-rlsv-pane="overview">
            <div class="mb-3">
                <h2 class="text-xs font-bold text-slate-500 uppercase tracking-widest m-0">{__("rlsv_autopost.stat_total")} Overview</h2>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
                <div class="bento-card col-span-2 lg:col-span-1 bg-gradient-to-br from-brand/5 to-white p-4 rounded-3xl border border-brand/20 shadow-sm flex flex-col justify-center relative overflow-hidden">
                    <div class="absolute -bottom-10 -right-10 w-24 h-24 bg-brand/10 rounded-full blur-2xl"></div>
                    <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest relative z-10">{__("rlsv_autopost.stat_total")}</span>
                    <h3 class="text-3xl font-bold text-slate-900 mt-1 relative z-10">{$rlsv_ov_dash.total|number_format:0:".":" "}</h3>
                </div>
                <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center">
                    <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest">{__("rlsv_autopost.stat_done")}</span>
                    <h3 class="text-3xl font-bold text-success mt-1">{$rlsv_ov_dash.done|number_format:0:".":" "}</h3>
                </div>
                <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center">
                    <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest">{__("rlsv_autopost.stat_error")}</span>
                    <h3 class="text-3xl font-bold text-danger mt-1">{$rlsv_ov_dash.error|number_format:0:".":" "}</h3>
                </div>
                <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center">
                    <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest">{__("rlsv_autopost.stat_pending")}</span>
                    <h3 class="text-3xl font-bold text-warning mt-1">{$rlsv_ov_dash.pending|number_format:0:".":" "}</h3>
                </div>
                <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center">
                    <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest">{__("rlsv_autopost.stat_success")}</span>
                    <h3 class="text-3xl font-bold text-brand mt-1">{$rlsv_ov_dash.success}%</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bento-card bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">{__("rlsv_autopost.chart_status")}</h3>
                    <div class="flex items-center justify-center">
                        {include file="addons/rlsv_autopost/views/rlsv_account/_chart.tpl" chart=$rlsv_ov_dash.status}
                    </div>
                </div>
                <div class="bento-card bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">{__("rlsv_autopost.chart_platforms")}</h3>
                    <div class="flex items-center justify-center">
                        {include file="addons/rlsv_autopost/views/rlsv_account/_chart.tpl" chart=$rlsv_ov_dash.platform}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <h2 class="text-xs font-bold text-slate-500 uppercase tracking-widest m-0">Recent Activity</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                {foreach from=$rlsv_stats.rows item="row"}
                <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col gap-2">
                    <div class="flex justify-between items-start">
                        <span class="text-sm font-bold text-slate-800">{$row.product_name|escape}</span>
                        <span class="text-[10px] text-slate-400">{if $row.processed_at}{$row.processed_at|date_format:"%d.%m.%Y %H:%M"}{else}&mdash;{/if}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="text-[10px] bg-slate-100 px-2 py-1 rounded text-slate-600">{$row.platform|escape}</span>

                        {if $row.status == 'done'}
                            <span class="text-[10px] bg-success/10 text-success px-2 py-1 rounded border border-success/20">{$row.status|escape}</span>
                        {elseif $row.status == 'error'}
                            <span class="text-[10px] bg-danger/10 text-danger px-2 py-1 rounded border border-danger/20">{$row.status|escape}</span>
                        {elseif $row.status == 'pending' || $row.status == 'processing'}
                            <span class="text-[10px] bg-warning/10 text-warning px-2 py-1 rounded border border-warning/20">{$row.status|escape}</span>
                        {else}
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200">{$row.status|escape}</span>
                        {/if}
                    </div>

                    {if $row.error}
                        <div class="mt-3 text-xs text-danger bg-danger/5 p-2.5 rounded-lg border border-danger/10 truncate" title="{$row.error|escape}">
                            {$row.error|escape|truncate:120}
                        </div>
                    {/if}
                </div>
                {foreachelse}
                <div class="bento-card col-span-1 md:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 text-center shadow-sm">
                    <span class="text-sm text-slate-400">{__("no_data")}</span>
                </div>
                {/foreach}
            </div>
        </div>

        {* ===== По каналам ===== *}
        {foreach from=$rlsv_platforms item="pf"}
            {assign var="ch" value=$rlsv_by_channel.$pf}
            {assign var="cd" value=$rlsv_ch_dash.$pf}
            <div data-rlsv-pane="ch_{$pf|escape}" style="display:none;">

                <div class="mb-3">
                    <h2 class="text-xs font-bold text-slate-500 uppercase tracking-widest m-0 capitalize">{$pf|escape} Stats</h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
                    <div class="bento-card col-span-2 lg:col-span-1 bg-gradient-to-br from-brand/5 to-white p-4 rounded-3xl border border-brand/20 shadow-sm flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute -bottom-10 -right-10 w-24 h-24 bg-brand/10 rounded-full blur-2xl"></div>
                        <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest relative z-10">{__("rlsv_autopost.stat_total")}</span>
                        <h3 class="text-3xl font-bold text-slate-900 mt-1 relative z-10">{$cd.total|number_format:0:".":" "}</h3>
                    </div>
                    <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center">
                        <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest">{__("rlsv_autopost.stat_done")}</span>
                        <h3 class="text-3xl font-bold text-success mt-1">{$cd.done|number_format:0:".":" "}</h3>
                    </div>
                    <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center">
                        <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest">{__("rlsv_autopost.stat_error")}</span>
                        <h3 class="text-3xl font-bold text-danger mt-1">{$cd.error|number_format:0:".":" "}</h3>
                    </div>
                    <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center">
                        <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest">{__("rlsv_autopost.stat_pending")}</span>
                        <h3 class="text-3xl font-bold text-warning mt-1">{$cd.pending|number_format:0:".":" "}</h3>
                    </div>
                    <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center">
                        <span class="text-[10px] font-medium text-slate-500 uppercase tracking-widest">{__("rlsv_autopost.stat_success")}</span>
                        <h3 class="text-3xl font-bold text-brand mt-1">{$cd.success}%</h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 mb-6">
                    <div class="bento-card bg-white p-5 rounded-3xl border border-slate-200 shadow-sm max-w-2xl">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">{__("rlsv_autopost.chart_status")}</h3>
                        <div class="flex items-center justify-center">
                            {include file="addons/rlsv_autopost/views/rlsv_account/_chart.tpl" chart=$cd.status}
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <h2 class="text-xs font-bold text-slate-500 uppercase tracking-widest m-0 capitalize">{$pf|escape} Log</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {foreach from=$ch.rows item="row"}
                    <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col gap-2">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-bold text-slate-800">{if $row.product_name}{$row.product_name|escape}{else}#{$row.product_id}{/if}</span>
                            <span class="text-[10px] text-slate-400">{if $row.processed_at}{$row.processed_at|date_format:"%d.%m.%Y %H:%M"}{else}&mdash;{/if}</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            {if $row.status == 'done'}
                                <span class="text-[10px] bg-success/10 text-success px-2 py-1 rounded border border-success/20">{$row.status|escape}</span>
                            {elseif $row.status == 'error'}
                                <span class="text-[10px] bg-danger/10 text-danger px-2 py-1 rounded border border-danger/20">{$row.status|escape}</span>
                            {elseif $row.status == 'pending' || $row.status == 'processing'}
                                <span class="text-[10px] bg-warning/10 text-warning px-2 py-1 rounded border border-warning/20">{$row.status|escape}</span>
                            {else}
                                <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200">{$row.status|escape}</span>
                            {/if}
                        </div>

                        {if $row.error}
                            <div class="mt-3 text-xs text-danger bg-danger/5 p-2.5 rounded-lg border border-danger/10 truncate" title="{$row.error|escape}">
                                {$row.error|escape|truncate:120}
                            </div>
                        {/if}
                    </div>
                    {foreachelse}
                    <div class="bento-card col-span-1 md:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 text-center shadow-sm">
                        <span class="text-sm text-slate-400">{__("no_data")}</span>
                    </div>
                    {/foreach}
                </div>
            </div>
        {/foreach}

        {* ===== По товарам ===== *}
        <div data-rlsv-pane="by_product" style="display:none;">
            <div class="mb-3">
                <h2 class="text-xs font-bold text-slate-500 uppercase tracking-widest m-0">{__("rlsv_autopost.tab_by_product")}</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {foreach from=$rlsv_by_product item="p"}
                <div class="bento-card bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <a href="{$p.url}" class="text-sm font-bold text-brand hover:text-brand-dark transition-colors" style="text-decoration: none;">{$p.product_name|escape}</a>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mb-4 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider">{__("rlsv_autopost.stat_total")}</span>
                                <span class="text-sm font-bold text-slate-800 mt-1">{$p.total}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider">{__("rlsv_autopost.stat_done")}</span>
                                <span class="text-sm font-bold text-success mt-1">{$p.done}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider">{__("rlsv_autopost.stat_error")}</span>
                                <span class="text-sm font-bold text-danger mt-1">{$p.err}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="text-[10px] text-slate-500 uppercase tracking-widest mb-2">{__("rlsv_autopost.stat_by_platform")}</div>
                            <div class="flex flex-wrap gap-2">
                                {foreach from=$p.platforms key="pf" item="pp" name="pp"}
                                    <div class="bg-slate-100 px-2 py-1 rounded text-[10px] text-slate-700 flex gap-1">
                                        <span class="capitalize font-medium">{$pf|escape}:</span>
                                        <span class="{if $pp.done == $pp.cnt}text-success{else}text-slate-900{/if} font-bold">{$pp.done}/{$pp.cnt}</span>
                                    </div>
                                {/foreach}
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-400 flex items-center gap-1 mb-4">
                            <i class="fa-regular fa-clock"></i>
                            {__("rlsv_autopost.last_published")}: {if $p.last_at}{$p.last_at|date_format:"%d.%m.%Y %H:%M"}{else}&mdash;{/if}
                        </div>
                    </div>

                    <a href="{$p.edit_url}" class="text-center w-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium py-2 rounded-xl transition-colors border border-slate-200" style="text-decoration: none;">
                        <i class="fa-solid fa-pen-to-square mr-1"></i> {__("rlsv_autopost.edit_description")}
                    </a>
                </div>
                {foreachelse}
                <div class="bento-card col-span-1 md:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 text-center shadow-sm">
                    <span class="text-sm text-slate-400">{__("no_data")}</span>
                </div>
                {/foreach}
            </div>
        </div>
    </main>

    <script>
    (function(){
        function init(){
            var root = document.getElementById('rlsv-ap-stats');
            if (!root) { return; }
            var tabs  = [].slice.call(root.querySelectorAll('[data-rlsv-tab]'));
            var panes = [].slice.call(root.querySelectorAll('[data-rlsv-pane]'));
            function activate(id){
                tabs.forEach(function(t){
                    if(t.getAttribute('data-rlsv-tab') === id) {
                        t.classList.add('active');
                    } else {
                        t.classList.remove('active');
                    }
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
