<div class="panel">

    <div class="panel-heading">
        <i class="icon-time"></i>
        Historial de sincronizaciones
    </div>

    {if empty($logs)}

        <div class="alert alert-info">
            No hay sincronizaciones registradas todavía.
        </div>

    {else}

        <table class="table table-striped">

            <thead>
            <tr>
                <th>Fecha</th>
                <th>Fuente</th>
                <th>Total</th>
                <th>Creados</th>
                <th>Actualizados</th>
                <th>Sin cambios</th>
                <th>Errores</th>
                <th>Estado</th>
                <th></th>
            </tr>
            </thead>

            <tbody>

            {foreach from=$logs item=log}

                <tr>

                    <td>
                        {$log.date_add|escape:'htmlall':'UTF-8'}
                    </td>

                    <td>
                        {$log.source_name|escape:'htmlall':'UTF-8'}
                    </td>

                    <td>
                        {$log.total}
                    </td>

                    <td>
                        {$log.created}
                    </td>

                    <td>
                        {$log.updated}
                    </td>

                    <td>
                        {$log.skipped}
                    </td>

                    <td>
                        {$log.errors}
                    </td>

                    <td>

                        {if $log.status == 'success'}

                            <span class="label label-success">
                                Éxito
                            </span>

                        {elseif $log.status == 'warning'}

                            <span class="label label-warning">
                                Advertencia
                            </span>

                        {else}

                            <span class="label label-danger">
                                Error
                            </span>

                        {/if}

                    </td>

                    <td class="text-right">

                        <a
                                href="{$log.detail_url}"
                                class="btn btn-default btn-sm"
                        >
                            <i class="icon-search"></i>
                            Ver detalle
                        </a>

                    </td>

                </tr>

            {/foreach}

            </tbody>

        </table>

    {/if}

    <div class="panel-footer">

        <a
                href="{$back_url}"
                class="btn btn-default"
        >
            <i class="icon-arrow-left"></i>
            Volver
        </a>

    </div>

</div>