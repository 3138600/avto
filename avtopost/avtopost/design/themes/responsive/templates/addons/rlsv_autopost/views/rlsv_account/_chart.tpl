{*
    Переиспользуемый блок «пончик + легенда».
    Ожидает: $chart = { donut: {...геометрия из fn_rlsv_autopost_ig_donut...}, parts: [{label,value,color}] }
    Палитра — Brightness.
*}
<div class="rlsv-donut-row">
    {if $chart.donut.total > 0}
        <div class="rlsv-donut">
            <svg width="{$chart.donut.size}" height="{$chart.donut.size}" viewBox="0 0 {$chart.donut.size} {$chart.donut.size}">
                <g transform="rotate(-90 {$chart.donut.c} {$chart.donut.c})">
                    <circle cx="{$chart.donut.c}" cy="{$chart.donut.c}" r="{$chart.donut.r}" fill="none" stroke="#edf2f5" stroke-width="{$chart.donut.sw}"/>
                    {foreach from=$chart.donut.segments item="s"}
                        <circle cx="{$chart.donut.c}" cy="{$chart.donut.c}" r="{$chart.donut.r}" fill="none" stroke="{$s.color}" stroke-width="{$chart.donut.sw}" stroke-dasharray="{$s.dasharray}" stroke-dashoffset="{$s.dashoffset}"/>
                    {/foreach}
                </g>
            </svg>
        </div>
    {/if}
    <div class="rlsv-legend">
        {foreach from=$chart.parts item="p"}
            <div class="rlsv-legend-row">
                <span class="dot" style="background:{$p.color};"></span>
                <span class="l">{$p.label|escape}</span>
                <span class="v">{$p.value|number_format:0:".":" "}</span>
            </div>
        {foreachelse}
            <div class="rlsv-muted">{__("no_data")}</div>
        {/foreach}
    </div>
</div>
