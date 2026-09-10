<div class="panel">
    <div class="panel-heading">
        <i class="icon-eye"></i>
        Dry Run
    </div>

    <div class="alert alert-info">
        <strong>{l s='Vista previa' mod='cpbsync'}</strong>
        <p>
            {l s='Se muestran los primeros 5 productos de la fuente.' mod='cpbsync'}
        </p>
        <strong>{l s='No se modificó ningún producto en PrestaShop.' mod='cpbsync'}</strong>
    </div>

    {foreach from=$products item=product}

        <div class="panel" style="margin-bottom: 20px;">

            <div class="panel-heading">
                {l s='Producto' mod='cpbsync'} {$product.number}

                {if $product.valid}
                    <span class="label label-success pull-right">
                        ✓ {l s='Válido' mod='cpbsync'}
                    </span>
                {else}
                    <span class="label label-danger pull-right">
                        ✗ {l s='Con errores' mod='cpbsync'}
                    </span>
                {/if}
            </div>

            {if !$product.valid}
                <div class="alert alert-danger">
                    <strong>{l s='Errores:' mod='cpbsync'}</strong>

                    <ul>
                        {foreach from=$product.errors item=error}
                            <li>
                                {$error|escape:'htmlall':'UTF-8'}
                            </li>
                        {/foreach}
                    </ul>
                </div>
            {/if}

            <table class="table">
                <thead>
                <tr>
                    <th>{l s='Campo PrestaShop' mod='cpbsync'}</th>
                    <th>{l s='Original' mod='cpbsync'}</th>
                    <th>{l s='Resultado' mod='cpbsync'}</th>
                </tr>
                </thead>

                <tbody>
                {foreach from=$product.data key=field item=fieldData}
                    <tr>
                        <td>
                            <strong>
                                {$field|escape:'htmlall':'UTF-8'}
                            </strong>
                        </td>

                        <td>
                            {$fieldData.original|escape:'htmlall':'UTF-8'}
                        </td>

                        <td>
                            <strong>
                                {$fieldData.value|escape:'htmlall':'UTF-8'}
                            </strong>

                            {if $fieldData.changed}
                                <span class="label label-warning" style="margin-left: 8px;">
                                    Cambiado
                                </span>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>

        </div>

    {/foreach}

    <div class="panel-footer">
        <a href="{$back_url}" class="btn btn-default">
            <i class="icon-arrow-left"></i>
            Volver al mapping
        </a>
    </div>
</div>