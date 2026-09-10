<div class="panel">

    <div class="panel-heading">
        <i class="icon-plus"></i>
        {l s='Nueva fuente' mod='cpbsync'}
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
                    {l s='Nombre' mod='cpbsync'}
                </label>

                <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="{if isset($source)}{$source.name|escape:'htmlall':'UTF-8'}{/if}"
                        placeholder="{l s='Ej. Proveedor ABC' mod='cpbsync'}"
                >
            </div>

            <div class="form-group">
                <label>
                    {l s='Tipo de fuente' mod='cpbsync'}
                </label>

                <select name="type" class="form-control">

                    <option
                            value="csv"
                            {if isset($source) && $source.type === 'csv'}selected{/if}
                    >
                        CSV
                    </option>

                </select>
            </div>

            <div class="form-group">
                <label>
                    {l s='URL de la fuente' mod='cpbsync'}
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
                <label>
                    {l s='Frecuencia' mod='cpbsync'}
                </label>

                <select name="frequency" class="form-control">

                    <option value="manual"
                            {if isset($source) && $source.frequency === 'manual'}selected{/if}>
                        {l s='Manual' mod='cpbsync'}
                    </option>

                    <option value="hourly"
                            {if isset($source) && $source.frequency === 'hourly'}selected{/if}>
                        {l s='Cada hora' mod='cpbsync'}
                    </option>

                    <option value="6_hours"
                            {if isset($source) && $source.frequency === '6_hours'}selected{/if}>
                        {l s='Cada 6 horas' mod='cpbsync'}
                    </option>

                    <option value="daily"
                            {if isset($source) && $source.frequency === 'daily'}selected{/if}>
                        {l s='Diariamente' mod='cpbsync'}
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

                    {l s='Fuente activa' mod='cpbsync'}
                </label>
            </div>

            <hr>

            <a
                    href="{$cancel_url|escape:'htmlall':'UTF-8'}"
                    class="btn btn-default"
            >
                {l s='Cancelar' mod='cpbsync'}
            </a>

            <button type="submit" class="btn btn-primary">
                <i class="icon-save"></i>
                {l s='Guardar' mod='cpbsync'}
            </button>

        </form>

    </div>

</div>