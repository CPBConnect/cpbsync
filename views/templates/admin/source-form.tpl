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

                <select name="frequency" class="form-control">

                    <option value="manual"
                            {if isset($source) && $source.frequency === 'manual'}selected{/if}>
                        {l s='Manual' d='Modules.Cpbsync.Admin'}
                    </option>

                    <option value="hourly"
                            {if isset($source) && $source.frequency === 'hourly'}selected{/if}>
                        {l s='Hourly' d='Modules.Cpbsync.Admin'}
                    </option>

                    <option value="6_hours"
                            {if isset($source) && $source.frequency === '6_hours'}selected{/if}>
                        {l s='Every 6 hours' d='Modules.Cpbsync.Admin'}
                    </option>

                    <option value="daily"
                            {if isset($source) && $source.frequency === 'daily'}selected{/if}>
                        {l s='Daily' d='Modules.Cpbsync.Admin'}
                    </option>

                </select>
            </div>

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