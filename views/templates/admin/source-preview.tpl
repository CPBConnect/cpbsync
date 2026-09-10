<div class="panel">

    <div class="panel-heading">
        <i class="icon-eye"></i>
        {l s='Vista previa de la fuente' mod='cpbsync'}
    </div>

    <div class="panel-body">

        <div class="alert alert-success">
            <i class="icon-check"></i>

            {l s='Conexión exitosa.' mod='cpbsync'}

            <strong>
                {$total}
            </strong>

            {l s='registros encontrados.' mod='cpbsync'}
        </div>

        <h4>
            {l s='Columnas detectadas' mod='cpbsync'}
        </h4>

        <div class="form-group">

            {foreach from=$headers item=header}

                <span class="label label-default" style="margin-right: 5px;">
                    {$header|escape:'htmlall':'UTF-8'}
                </span>

            {/foreach}

        </div>

        <hr>

        <h4>
            {l s='Primeros registros' mod='cpbsync'}
        </h4>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead>
                <tr>

                    {foreach from=$headers item=header}

                        <th>
                            {$header|escape:'htmlall':'UTF-8'}
                        </th>

                    {/foreach}

                </tr>
                </thead>

                <tbody>

                {foreach from=$rows item=row}

                    <tr>

                        {foreach from=$headers item=header}

                            <td>
                                {$row[$header]|escape:'htmlall':'UTF-8'}
                            </td>

                        {/foreach}

                    </tr>

                {/foreach}

                </tbody>

            </table>

        </div>

        <hr>

        <a
                href="{$back_url|escape:'htmlall':'UTF-8'}"
                class="btn btn-default"
        >
            <i class="icon-arrow-left"></i>
            {l s='Volver a fuentes' mod='cpbsync'}
        </a>

    </div>

</div>