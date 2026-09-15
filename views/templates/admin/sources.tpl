<div class="panel">

    <div class="panel-heading">
        <i class="icon-refresh"></i>
        {l s='CPB Sync' mod='cpbsync'}
    </div>

    <div class="panel-body">

        <div class="row">
            <div class="col-md-8">

                <h3>
                    {l s='Fuentes de datos' mod='cpbsync'}
                </h3>

                <p class="text-muted">
                    {l s='Conecta proveedores, ERP y otras fuentes de productos con PrestaShop.' mod='cpbsync'}
                </p>

            </div>

            <div class="col-md-4 text-right">
                <a
                        href="{$import_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-primary"
                >
                    {l s='Importar CSV' mod='cpbsync'}
                </a>
                <a
                        href="{$history_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-default"
                >
                    <i class="icon-time"></i>
                    {l s='Historial' mod='cpbsync'}
                </a>
                <a
                        href="{$source_form_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-primary"
                >
                    <i class="icon-plus"></i>
                    {l s='Agregar fuente' mod='cpbsync'}
                </a>

            </div>
        </div>

        <hr>

        {if empty($sources)}

            <div class="alert alert-info">
                <i class="icon-info-circle"></i>
                {l s='Aún no tienes fuentes configuradas.' mod='cpbsync'}
            </div>

        {else}

            {foreach from=$sources item=source}

                <div class="panel">

                    <div class="panel-heading">
                        {$source.name|escape:'htmlall':'UTF-8'}
                    </div>

                    <div class="panel-body">

                        <p>
                            <strong>
                                {l s='Tipo:' mod='cpbsync'}
                            </strong>

                            {$source.type|escape:'htmlall':'UTF-8'}
                        </p>

                        <p>
                            <strong>
                                {l s='URL:' mod='cpbsync'}
                            </strong>

                            {$source.url|escape:'htmlall':'UTF-8'}
                        </p>

                        <p>
                            <strong>
                                {l s='Frecuencia:' mod='cpbsync'}
                            </strong>

                            {$source.frequency|escape:'htmlall':'UTF-8'}
                        </p>

                        <p>
                            <strong>
                                {l s='Estado:' mod='cpbsync'}
                            </strong>

                            {if $source.active}
                                <span class="label label-success">
                                    {l s='Activa' mod='cpbsync'}
                                </span>
                            {else}
                                <span class="label label-default">
                                    {l s='Inactiva' mod='cpbsync'}
                                </span>
                            {/if}
                        </p>

                        <hr>

                        <div class="text-right">
                            <a
                                    href="{$source.test_url|escape:'htmlall':'UTF-8'}"
                                    class="btn btn-info"
                            >
                                <i class="icon-refresh"></i>
                                {l s='Probar conexión' mod='cpbsync'}
                            </a>

                            <a
                                    href="{$source.map_url|escape:'htmlall':'UTF-8'}"
                                    class="btn btn-default"
                            >
                                <i class="icon-random"></i>
                                {l s='Mapear' mod='cpbsync'}
                            </a>

                            <a
                                    href="{$source.edit_url|escape:'htmlall':'UTF-8'}"
                                    class="btn btn-default"
                            >
                                <i class="icon-edit"></i>
                                {l s='Editar' mod='cpbsync'}
                            </a>

                            <form
                                    method="post"
                                    action="{$source.delete_url|escape:'htmlall':'UTF-8'}"
                                    style="display:inline;"
                                    onsubmit="return confirm('{l s='¿Estás seguro de eliminar esta fuente?' mod='cpbsync'}');"
                            >
                                <button
                                        type="submit"
                                        class="btn btn-danger"
                                >
                                    <i class="icon-trash"></i>
                                    {l s='Eliminar' mod='cpbsync'}
                                </button>
                            </form>

                        </div>

                    </div>

                </div>

            {/foreach}

        {/if}

    </div>

</div>