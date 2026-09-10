<div class="panel">

    <div class="panel-heading">
        <i class="icon-search"></i>
        Detalle de sincronización
    </div>

    <div class="alert alert-info">

        <strong>
            Ejecución #{$log.id_log}
        </strong>

        <p>
            Fecha:
            {$log.date_add|escape:'htmlall':'UTF-8'}
        </p>

        <p>
            Fuente:

            {if $source}
                {$source.name|escape:'htmlall':'UTF-8'}
            {else}
                Fuente eliminada
            {/if}
        </p>

    </div>

    <div class="row">

        <div class="col-md-2">
            <div class="well text-center">
                <strong>Total</strong>
                <br>
                {$log.total}
            </div>
        </div>

        <div class="col-md-2">
            <div class="well text-center">
                <strong>Creados</strong>
                <br>
                {$log.created}
            </div>
        </div>

        <div class="col-md-2">
            <div class="well text-center">
                <strong>Actualizados</strong>
                <br>
                {$log.updated}
            </div>
        </div>

        <div class="col-md-2">
            <div class="well text-center">
                <strong>Sin cambios</strong>
                <br>
                {$log.skipped}
            </div>
        </div>

        <div class="col-md-2">
            <div class="well text-center">
                <strong>Errores</strong>
                <br>
                {$log.errors}
            </div>
        </div>

    </div>

</div>


<div class="panel">

    <div class="panel-heading">
        Productos procesados
    </div>

    {if empty($details)}

        <div class="alert alert-info">
            No hay detalles disponibles.
        </div>

    {else}

        <table class="table table-striped">

            <thead>
            <tr>
                <th>Referencia</th>
                <th>Estado</th>
                <th>ID producto</th>
                <th>Errores</th>
            </tr>
            </thead>

            <tbody>

            {foreach from=$details item=item}

                <tr>

                    <td>
                        {$item.reference|default:''|escape:'htmlall':'UTF-8'}
                    </td>

                    <td>

                        {if $item.status == 'created'}

                            <span class="label label-success">
                                Creado
                            </span>

                        {elseif $item.status == 'updated'}

                            <span class="label label-info">
                                Actualizado
                            </span>

                        {elseif $item.status == 'skipped'}

                            <span class="label label-default">
                                Sin cambios
                            </span>

                        {else}

                            <span class="label label-danger">
                                Error
                            </span>

                        {/if}

                    </td>

                    <td>
                        {$item.id_product|default:'-' }
                    </td>

                    <td>

                        {if !empty($item.errors)}

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

    {/if}

    <div class="panel-footer">

        <a
                href="{$back_url}"
                class="btn btn-default"
        >
            <i class="icon-arrow-left"></i>
            Volver al historial
        </a>

    </div>

</div>