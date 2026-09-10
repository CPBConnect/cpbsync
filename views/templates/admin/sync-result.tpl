<div class="panel">
    <div class="panel-heading">
        <i class="icon-refresh"></i>
        {l s='Resultado de sincronización' mod='cpbsync'}
    </div>

    <div class="alert alert-info">
        <strong>{l s='Sincronización completada' mod='cpbsync'}/strong>
    </div>

    {if isset($log_id)}
        <div class="alert alert-info">
            <strong>
                {l s='Ejecución' mod='cpbsync'} #{$log_id}
            </strong>

            <p>
                {l s='Esta sincronización quedó registrada en el historial.' mod='cpbsync'}
            </p>
        </div>
    {/if}

    <div class="row">

        <div class="col-md-2">
            <div class="well text-center">
                <strong>{l s='Total' mod='cpbsync'}</strong>
                <br>
                {$result.total}
            </div>
        </div>

        <div class="col-md-2">
            <div class="alert alert-success text-center">
                <strong>{l s='Creado' mod='cpbsync'}</strong><br>
                <span style="font-size: 24px;">
                    {$result.created}
                </span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="alert alert-info text-center">
                <strong>{l s='Actualizado' mod='cpbsync'}</strong><br>
                <span style="font-size: 24px;">
            {$result.updated}
        </span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="alert alert-warning text-center">
                <strong>{l s='Omitido' mod='cpbsync'}</strong><br>
                <span style="font-size: 24px;">
                    {$result.skipped}
                </span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="alert alert-danger text-center">
                <strong>{l s='Errores' mod='cpbsync'}</strong><br>
                <span style="font-size: 24px;">
                    {$result.errors}
                </span>
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>{l s='Referencia' mod='cpbsync'}</th>
            <th>{l s='Estado' mod='cpbsync'}</th>
            <th>{l s='ID PrestaShop' mod='cpbsync'}</th>
            <th>{l s='Detalle' mod='cpbsync'}</th>
        </tr>
        </thead>

        <tbody>
        {foreach from=$result.items item=item}
            <tr>
                <td>
                    {$item.reference|escape:'htmlall':'UTF-8'}
                </td>

                <td>
                    {if $item.status === 'created'}
                        <span class="label label-success">
                            {l s='Creado' mod='cpbsync'}
                        </span>
                    {elseif $item.status === 'updated'}
                        <span class="label label-info">
                            {l s='Actualizado' mod='cpbsync'}
                        </span>
                    {elseif $item.status === 'skipped'}
                        <span class="label label-warning">
                            {l s='Omitido' mod='cpbsync'}
                        </span>
                    {else}
                        <span class="label label-danger">
                            {l s='Errores' mod='cpbsync'}
                        </span>
                    {/if}
                </td>

                <td>
                    {if isset($item.id_product)}
                        {$item.id_product}
                    {else}
                        -
                    {/if}
                </td>

                <td>
                    {if isset($item.errors)}
                        <ul>
                            {foreach from=$item.errors item=error}
                                <li>
                                    {$error|escape:'htmlall':'UTF-8'}
                                </li>
                            {/foreach}
                        </ul>
                    {else}
                        -
                    {/if}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>

    <div class="panel-footer">
        <a href="{$back_url}" class="btn btn-default">
            <i class="icon-arrow-left"></i>
            {l s='Volver al mapping' mod='cpbsync'}
        </a>
    </div>
</div>