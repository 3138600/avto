{capture name="mainbox"}
{if $rlsv_user_id}

    <div class="control-group">
        <strong>{$rlsv_user.name} </strong>
        {if $rlsv_user.email}&lt;{$rlsv_user.email}&gt;{/if}
        &mdash; <a href="{"profiles.update?user_id=`$rlsv_user_id`"|fn_url}">{__("rlsv_autopost.back_to_profile")}</a>
    </div>

    {* Сводка *}
    <div class="table-responsive-wrapper">
        <table width="100%" class="table table-middle">
            <thead>
                <tr>
                    <th>{__("rlsv_autopost.stat_total")}</th>
                    <th>{__("rlsv_autopost.stat_done")}</th>
                    <th>{__("rlsv_autopost.stat_error")}</th>
                    <th>{__("rlsv_autopost.stat_pending")}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{$rlsv_stats.total}</td>
                    <td>{$rlsv_stats.done}</td>
                    <td>{$rlsv_stats.error}</td>
                    <td>{$rlsv_stats.pending}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {if $rlsv_stats.by_platform}
        <p><strong>{__("rlsv_autopost.stat_by_platform")}:</strong>
            {foreach from=$rlsv_stats.by_platform key="pf" item="cnt" name="pf"}
                {$pf}: {$cnt}{if !$smarty.foreach.pf.last}, {/if}
            {/foreach}
        </p>
    {/if}

    {* Журнал публикаций *}
    <div class="table-responsive-wrapper">
        <table width="100%" class="table table-middle table-responsive">
            <thead>
                <tr>
                    <th width="35%">{__("rlsv_autopost.col_product")}</th>
                    <th width="12%">{__("rlsv_autopost.col_platform")}</th>
                    <th width="10%">{__("rlsv_autopost.col_status")}</th>
                    <th width="18%">{__("rlsv_autopost.col_date")}</th>
                    <th width="25%">{__("rlsv_autopost.col_error")}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$rlsv_stats.rows item="row"}
                    <tr>
                        <td><a href="{"products.update?product_id=`$row.product_id`"|fn_url}">{$row.product_name}</a></td>
                        <td>{$row.platform}</td>
                        <td>{$row.status}</td>
                        <td>{if $row.processed_at}{$row.processed_at|date_format:"%d.%m.%Y %H:%M"}{else}&mdash;{/if}</td>
                        <td>{$row.error}</td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="5">{__("no_data")}</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>

{else}

    {* Очистка от мусора *}
    <fieldset>
        <legend>{__("rlsv_autopost.cleanup_legend")}</legend>
        <form action="{"rlsv_autopost.cleanup"|fn_url}" method="post" name="rlsv_cleanup_form">
            <input type="hidden" name="redirect_url" value="{$config.current_url}" />
            <div class="control-group">
                <label class="control-label" for="rlsv_cleanup_days">{__("rlsv_autopost.cleanup_old")}</label>
                <div class="controls">
                    <input type="text" id="rlsv_cleanup_days" name="days" value="30" size="4" /> {__("rlsv_autopost.days")}
                    <button class="btn" type="submit" name="what" value="old">{__("rlsv_autopost.cleanup_old_btn")}</button>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button class="btn" type="submit" name="what" value="done">{__("rlsv_autopost.cleanup_done_btn")}</button>
                    <button class="btn" type="submit" name="what" value="errors">{__("rlsv_autopost.cleanup_errors_btn")}</button>
                    <button class="btn" type="submit" name="what" value="orphans">{__("rlsv_autopost.cleanup_orphans_btn")}</button>
                    <button class="btn btn-danger cm-confirm" type="submit" name="what" value="all_queue">{__("rlsv_autopost.cleanup_all_btn")}</button>
                </div>
            </div>
        </form>
    </fieldset>

    {* Обзор по всем издателям *}
    <div class="table-responsive-wrapper">
        <table width="100%" class="table table-middle table-responsive">
            <thead>
                <tr>
                    <th>{__("rlsv_autopost.col_user")}</th>
                    <th>{__("rlsv_autopost.stat_total")}</th>
                    <th>{__("rlsv_autopost.stat_done")}</th>
                    <th>{__("rlsv_autopost.stat_error")}</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$rlsv_overview item="o"}
                    <tr>
                        <td>{$o.name} {if $o.email}&lt;{$o.email}&gt;{/if}</td>
                        <td>{$o.total}</td>
                        <td>{$o.done}</td>
                        <td>{$o.err}</td>
                        <td><a href="{"rlsv_autopost.stats?user_id=`$o.user_id`"|fn_url}">{__("rlsv_autopost.details")}</a></td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="5">{__("no_data")}</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>

{/if}
{/capture}

{include file="common/mainbox.tpl" title=__("rlsv_autopost.stats") content=$smarty.capture.mainbox}
