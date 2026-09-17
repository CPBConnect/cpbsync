<div class="panel">

    <div class="panel-heading">
        <i class="icon-plus"></i>
        {l s='New source' d='Modules.Cpbsync.Admin'}
    </div>

    <div class="panel-body">
        {if isset($form_error) && $form_error}
            <div class="alert alert-danger">
                {$form_error|escape:'htmlall':'UTF-8'}
            </div>
        {/if}
        <form
                method="post"
                action="{$form_action|escape:'htmlall':'UTF-8'}"
        >

            <div class="form-group">
                <label>
                    {l s='Name' d='Modules.Cpbsync.Admin'}
                </label>

                <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="{if isset($source)}{$source.name|escape:'htmlall':'UTF-8'}{/if}"
                        placeholder="{l s='E.g. Supplier ABC' d='Modules.Cpbsync.Admin'}"
                >
            </div>

            <div class="form-group">
                <label>
                    {l s='Source type' d='Modules.Cpbsync.Admin'}
                </label>

                <select name="type" class="form-control">

                    {foreach from=$source_types key=source_type item=source_label}
                        <option
                                value="{$source_type|escape:'htmlall':'UTF-8'}"
                                {if isset($source) && $source.type === $source_type}selected{/if}
                        >
                            {$source_label|escape:'htmlall':'UTF-8'}
                        </option>
                    {/foreach}

                </select>
            </div>

            <div class="form-group">
                <label>
                    {l s='Source URL' d='Modules.Cpbsync.Admin'}
                </label>

                <input
                        type="url"
                        name="url"
                        class="form-control"
                        value="{if isset($source)}{$source.url|escape:'htmlall':'UTF-8'}{/if}"
                        placeholder="https://proveedor.com/catalogo.csv"
                >
            </div>

            <div class="form-group">
                <label for="config">
                    {l s='Additional configuration (JSON)' d='Modules.Cpbsync.Admin'}
                </label>

                <textarea
                        name="config"
                        id="config"
                        class="form-control"
                        rows="2"
                >{if isset($source)}{$source.config|escape:'htmlall':'UTF-8'}{/if}</textarea>

                <p class="help-block">
                    {l s='Optional. XML and JSON sources use the record_path setting to locate the records.' d='Modules.Cpbsync.Admin'}
                </p>
            </div>

            <div class="form-group">
                <label>
                    {l s='Frequency' d='Modules.Cpbsync.Admin'}
                </label>

                <select name="frequency" class="form-control js-frequency">

                    {foreach from=$frequency_options item=frequency_option}

                        <option
                                value="{$frequency_option.name|escape:'htmlall':'UTF-8'}"
                                {if isset($source) && $source.frequency === $frequency_option.name}selected{/if}
                        >
                            {$frequency_option.label|escape:'htmlall':'UTF-8'}
                        </option>

                    {/foreach}

                </select>

                <p class="help-block">
                    {l s='The frequencies other than Manual need the cron command configured on the server.' d='Modules.Cpbsync.Admin'}
                </p>
            </div>

            {foreach from=$frequency_options item=frequency_option}

                {if !empty($frequency_option.fields)}

                    <div
                            class="js-frequency-fields"
                            data-frequency="{$frequency_option.name|escape:'htmlall':'UTF-8'}"
                            style="display: none;"
                    >

                        {foreach from=$frequency_option.fields item=field}

                            <div class="form-group">

                                <label>
                                    {$field.label|escape:'htmlall':'UTF-8'}
                                </label>

                                {if $field.type == 'select'}

                                    <select
                                            name="schedule_config[{$field.name|escape:'htmlall':'UTF-8'}]"
                                            class="form-control"
                                    >
                                        {foreach from=$field.options item=field_option}
                                            <option
                                                    value="{$field_option.value|escape:'htmlall':'UTF-8'}"
                                                    {if isset($saved_schedule[$field.name])}
                                                        {if $saved_schedule[$field.name] == $field_option.value}selected{/if}
                                                    {elseif $field.default == $field_option.value}
                                                        selected
                                                    {/if}
                                            >
                                                {$field_option.label|escape:'htmlall':'UTF-8'}
                                            </option>
                                        {/foreach}
                                    </select>

                                {elseif $field.type == 'time'}

                                    <input
                                            type="time"
                                            name="schedule_config[{$field.name|escape:'htmlall':'UTF-8'}]"
                                            class="form-control"
                                            value="{if isset($saved_schedule[$field.name])}{$saved_schedule[$field.name]|escape:'htmlall':'UTF-8'}{else}{$field.default|escape:'htmlall':'UTF-8'}{/if}"
                                    >

                                {else}

                                    <input
                                            type="text"
                                            name="schedule_config[{$field.name|escape:'htmlall':'UTF-8'}]"
                                            class="form-control"
                                            value="{if isset($saved_schedule[$field.name])}{$saved_schedule[$field.name]|escape:'htmlall':'UTF-8'}{else}{$field.default|escape:'htmlall':'UTF-8'}{/if}"
                                    >

                                {/if}

                                {if $field.hint}
                                    <p class="help-block">
                                        {$field.hint|escape:'htmlall':'UTF-8'}
                                    </p>
                                {/if}

                            </div>

                        {/foreach}

                    </div>

                {/if}

            {/foreach}

            <div class="form-group">
                <label>
                    <input
                            type="checkbox"
                            name="active"
                            value="1"
                            {if !isset($source) || $source.active}checked{/if}
                    >

                    {l s='Active source' d='Modules.Cpbsync.Admin'}
                </label>
            </div>

            {if !empty($sync_options)}

                <div class="form-group">

                    <label>
                        {l s='Synchronization options' d='Modules.Cpbsync.Admin'}
                    </label>

                    {foreach from=$sync_options item=option}

                        <div class="checkbox">

                            <label>

                                <input
                                        type="checkbox"
                                        name="sync_options[{$option.name|escape:'htmlall':'UTF-8'}]"
                                        value="1"
                                        {if !empty($saved_options[$option.name])}checked{/if}
                                >

                                {$option.label|escape:'htmlall':'UTF-8'}

                            </label>

                            {if $option.hint}
                                <p class="help-block">
                                    {$option.hint|escape:'htmlall':'UTF-8'}
                                </p>
                            {/if}

                        </div>

                    {/foreach}

                </div>

            {/if}

            <hr>

            <a
                    href="{$cancel_url|escape:'htmlall':'UTF-8'}"
                    class="btn btn-default"
            >
                {l s='Cancel' d='Modules.Cpbsync.Admin'}
            </a>

            <button type="submit" class="btn btn-primary">
                <i class="icon-save"></i>
                {l s='Save' d='Modules.Cpbsync.Admin'}
            </button>

        </form>

    </div>

</div>

<script>
    (function () {

        var select = document.querySelector('.js-frequency');
        var blocks = document.querySelectorAll('.js-frequency-fields');

        if (!select) {
            return;
        }

        function refresh() {
            blocks.forEach(function (block) {
                block.style.display =
                    block.dataset.frequency === select.value
                        ? ''
                        : 'none';
            });
        }

        select.addEventListener('change', refresh);

        refresh();
    })();
</script>