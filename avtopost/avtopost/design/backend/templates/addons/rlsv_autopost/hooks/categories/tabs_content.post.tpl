{*
    Вкладка «Автопостинг» в карточке категории.
    Хук: categories:tabs_content  (регистрируется в controllers/backend/categories.post.php)
    Поле — отдельный ключ rlsv_autopost_category[...] (НЕ category_data).
    Отмеченные категории добавляются к фильтру публикации (см. is_in_target_category).
*}
<div id="content_rlsv_autopost">
    {assign var="c" value=$rlsv_autopost_category}

    <div class="control-group">
        <label class="control-label" for="rlsv_ap_cat">{__("rlsv_autopost.publish_category")}</label>
        <div class="controls">
            <input type="hidden" name="rlsv_autopost_category[autopost]" value="N" />
            <input type="checkbox" id="rlsv_ap_cat" name="rlsv_autopost_category[autopost]" value="Y"
                   {if $c.autopost == "Y"}checked="checked"{/if} />
            <p class="muted">{__("rlsv_autopost.publish_category_hint")}</p>
        </div>
    </div>
</div>
