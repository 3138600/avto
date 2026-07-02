{capture name="mainbox"}

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
                        DEFAULT: '#6366f1',
                        light: '#818cf8',
                        dark: '#4f46e5'
                    },
                    success: '#10b981',
                    danger: '#f43f5e',
                }
            }
        }
    }
</script>

<style>
    /* Custom scrollbar hiding for clean UI */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Smooth transitions for interactive elements */
    .bento-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .bento-card:active {
        transform: scale(0.98);
    }
</style>

<div class="tw-scope w-full bg-slate-50 min-h-screen relative shadow-lg overflow-hidden flex flex-col border-x border-slate-200 mx-auto" style="max-width: 800px; font-family: 'Inter', sans-serif;">

{if $rlsv_user_id}

    <!-- 1. Sticky Header -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200 px-5 py-4 flex justify-between items-center" style="margin: 0;">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight m-0 p-0 leading-tight">{$rlsv_user.name}</h1>
            {if $rlsv_user.email}<p class="text-xs text-slate-500 mt-0.5 mb-0 leading-tight">{$rlsv_user.email}</p>{/if}
        </div>

        <a href="{"profiles.update?user_id=`$rlsv_user_id`"|fn_url}" class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-full border border-slate-200 transition-colors" style="text-decoration: none;">
            <i class="fa-solid fa-arrow-left text-brand text-xs"></i>
            <span class="text-xs font-medium text-slate-700">{__("rlsv_autopost.back_to_profile")}</span>
        </a>
    </header>

    <!-- Main Content (Scrollable) -->
    <main class="flex-1 overflow-y-auto no-scrollbar pb-24 px-4 pt-4">

        <!-- SECTION 1: БАЗОВЫЕ МЕТРИКИ -->
        <div class="mb-3 mt-1 pl-1">
            <h2 class="text-[10px] font-bold text-brand uppercase tracking-widest m-0 p-0">{__("rlsv_autopost.stat_total")}</h2>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-6">
            <!-- Всего задач (Hero Card) -->
            <div class="bento-card col-span-2 bg-gradient-to-br from-brand/5 to-white p-5 rounded-3xl border border-brand/20 shadow-sm relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-brand/10 rounded-full blur-3xl"></div>

                <div class="flex justify-between items-start mb-2 relative z-10">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-brand/10 flex items-center justify-center">
                            <i class="fa-solid fa-list-check text-brand text-sm"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-700">{__("rlsv_autopost.stat_total")}</span>
                    </div>
                </div>

                <div class="relative z-10 mt-3">
                    <h2 class="text-4xl font-bold text-slate-900 tracking-tight m-0 leading-none">{$rlsv_stats.total}</h2>
                </div>
            </div>

            <!-- Успешно -->
            <div class="bento-card col-span-1 bg-white p-4 rounded-3xl border border-slate-200 flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-xs font-medium text-slate-500">{__("rlsv_autopost.stat_done")}</span>
                    <i class="fa-solid fa-check text-success text-xs"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-success m-0 leading-none">{$rlsv_stats.done}</h3>
                </div>
            </div>

            <!-- Ошибки -->
            <div class="bento-card col-span-1 bg-white p-4 rounded-3xl border border-slate-200 flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-xs font-medium text-slate-500">{__("rlsv_autopost.stat_error")}</span>
                    <i class="fa-solid fa-triangle-exclamation text-danger text-xs"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-danger m-0 leading-none">{$rlsv_stats.error}</h3>
                </div>
            </div>

            <!-- В ожидании -->
            <div class="bento-card col-span-2 bg-white p-4 rounded-3xl border border-slate-200 flex justify-between items-center mt-2 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                        <i class="fa-regular fa-clock text-slate-500"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700">{__("rlsv_autopost.stat_pending")}</span>
                </div>
                <span class="text-2xl font-bold text-slate-900 leading-none">{$rlsv_stats.pending}</span>
            </div>
        </div>

        {if $rlsv_stats.by_platform}
        <div class="mb-3 pl-1 flex items-center gap-2 mt-4">
            <i class="fa-solid fa-share-nodes text-brand text-xs"></i>
            <h2 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest m-0">{__("rlsv_autopost.stat_by_platform")}</h2>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="bento-card col-span-2 bg-white p-4 rounded-3xl border border-slate-200 flex flex-wrap gap-2 shadow-sm">
                {foreach from=$rlsv_stats.by_platform key="pf" item="cnt" name="pf"}
                    <div class="bg-slate-50 px-3 py-1.5 rounded-full border border-slate-200 flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-600">{$pf}</span>
                        <span class="text-xs font-bold text-brand">{$cnt}</span>
                    </div>
                {/foreach}
            </div>
        </div>
        {/if}

        <!-- Журнал публикаций -->
        <div class="mb-3 pl-1 flex items-center gap-2 mt-4">
            <i class="fa-solid fa-rectangle-list text-danger text-xs"></i>
            <h2 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest m-0">{__("rlsv_autopost.col_product")} Log</h2>
        </div>

        <div class="grid grid-cols-1 gap-3">
            {foreach from=$rlsv_stats.rows item="row"}
            <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 flex flex-col gap-2 shadow-sm">
                <div class="flex justify-between items-start">
                    <a href="{"products.update?product_id=`$row.product_id`"|fn_url}" class="text-sm font-bold text-slate-800 hover:text-brand transition-colors" style="text-decoration: none;">{$row.product_name}</a>
                    <span class="text-[10px] text-slate-400">{if $row.processed_at}{$row.processed_at|date_format:"%d.%m.%Y %H:%M"}{else}&mdash;{/if}</span>
                </div>

                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <span class="text-[10px] bg-slate-100 px-2 py-1 rounded text-slate-600">{$row.platform}</span>

                    {if $row.status == 'done'}
                        <span class="text-[10px] bg-success/10 text-success px-2 py-1 rounded border border-success/20">{$row.status}</span>
                    {elseif $row.status == 'error'}
                        <span class="text-[10px] bg-danger/10 text-danger px-2 py-1 rounded border border-danger/20">{$row.status}</span>
                    {else}
                        <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200">{$row.status}</span>
                    {/if}
                </div>

                {if $row.error}
                    <div class="mt-3 text-xs text-danger bg-danger/5 p-2.5 rounded-lg border border-danger/10">
                        {$row.error}
                    </div>
                {/if}
            </div>
            {foreachelse}
            <div class="bento-card bg-white p-6 rounded-3xl border border-slate-200 text-center shadow-sm">
                <span class="text-sm text-slate-400">{__("no_data")}</span>
            </div>
            {/foreach}
        </div>

        <div class="h-10"></div>
    </main>

{else}

    <!-- 1. Sticky Header (Overview) -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200 px-5 py-4 flex justify-between items-center" style="margin: 0;">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight m-0 p-0 leading-tight">{__("rlsv_autopost.stats")}</h1>
            <p class="text-xs text-slate-500 mt-0.5 mb-0 leading-tight">Overview</p>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto no-scrollbar pb-24 px-4 pt-4">

        <!-- Cleanup Form -->
        <div class="mb-3 mt-1 pl-1">
            <h2 class="text-[10px] font-bold text-brand uppercase tracking-widest m-0">{__("rlsv_autopost.cleanup_legend")}</h2>
        </div>

        <div class="bento-card bg-white p-5 rounded-3xl border border-slate-200 mb-6 shadow-sm">
            <form action="{"rlsv_autopost.cleanup"|fn_url}" method="post" name="rlsv_cleanup_form" class="flex flex-col gap-4 m-0">
                <input type="hidden" name="redirect_url" value="{$config.current_url}" />

                <div class="flex flex-col gap-2">
                    <label class="text-xs font-medium text-slate-700" for="rlsv_cleanup_days">{__("rlsv_autopost.cleanup_old")}</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="rlsv_cleanup_days" name="days" value="30" class="bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-sm text-slate-800 w-20 focus:outline-none focus:border-brand" />
                        <span class="text-xs text-slate-500">{__("rlsv_autopost.days")}</span>
                        <button type="submit" name="what" value="old" class="ml-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-medium py-1.5 px-3 rounded-lg transition-colors border border-slate-200 cursor-pointer">{__("rlsv_autopost.cleanup_old_btn")}</button>
                    </div>
                </div>

                <div class="h-px w-full bg-slate-100 my-2"></div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" name="what" value="done" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-medium py-1.5 px-3 rounded-lg transition-colors border border-slate-200 cursor-pointer">{__("rlsv_autopost.cleanup_done_btn")}</button>
                    <button type="submit" name="what" value="errors" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-medium py-1.5 px-3 rounded-lg transition-colors border border-slate-200 cursor-pointer">{__("rlsv_autopost.cleanup_errors_btn")}</button>
                    <button type="submit" name="what" value="orphans" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-medium py-1.5 px-3 rounded-lg transition-colors border border-slate-200 cursor-pointer">{__("rlsv_autopost.cleanup_orphans_btn")}</button>
                    <button type="submit" name="what" value="all_queue" class="cm-confirm bg-danger/10 hover:bg-danger/20 text-danger text-xs font-medium py-1.5 px-3 rounded-lg transition-colors border border-danger/20 mt-2 w-full cursor-pointer">{__("rlsv_autopost.cleanup_all_btn")}</button>
                </div>
            </form>
        </div>

        <!-- User Overview -->
        <div class="mb-3 pl-1 flex items-center gap-2 mt-4">
            <i class="fa-solid fa-users text-brand text-xs"></i>
            <h2 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest m-0">{__("rlsv_autopost.col_user")}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            {foreach from=$rlsv_overview item="o"}
            <div class="bento-card bg-white p-4 rounded-3xl border border-slate-200 flex flex-col justify-between shadow-sm">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-slate-800 m-0">{$o.name}</h3>
                    {if $o.email}<span class="text-xs text-slate-500 block mt-1">{$o.email}</span>{/if}
                </div>

                <div class="grid grid-cols-3 gap-2 mb-4">
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider">{__("rlsv_autopost.stat_total")}</span>
                        <span class="text-sm font-bold text-slate-800 mt-1">{$o.total}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider">{__("rlsv_autopost.stat_done")}</span>
                        <span class="text-sm font-bold text-success mt-1">{$o.done}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider">{__("rlsv_autopost.stat_error")}</span>
                        <span class="text-sm font-bold text-danger mt-1">{$o.err}</span>
                    </div>
                </div>

                <a href="{"rlsv_autopost.stats?user_id=`$o.user_id`"|fn_url}" class="text-center w-full bg-brand/5 hover:bg-brand/10 text-brand text-xs font-medium py-2 rounded-xl transition-colors border border-brand/10" style="text-decoration: none;">
                    {__("rlsv_autopost.details")}
                </a>
            </div>
            {foreachelse}
            <div class="bento-card col-span-2 bg-white p-6 rounded-3xl border border-slate-200 text-center shadow-sm">
                <span class="text-sm text-slate-400">{__("no_data")}</span>
            </div>
            {/foreach}
        </div>

        <div class="h-10"></div>
    </main>

{/if}

</div>
{/capture}

{include file="common/mainbox.tpl" title=__("rlsv_autopost.stats") content=$smarty.capture.mainbox}
