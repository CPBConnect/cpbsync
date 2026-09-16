<div class="panel">
    <div class="panel-heading">
        <i class="icon-refresh"></i>
        {l s='Synchronization result' d='Modules.Cpbsync.Admin'}
    </div>

    <div class="alert alert-info">
        <strong>{l s='Synchronization completed' d='Modules.Cpbsync.Admin'}</strong>
    </div>

    {if isset($log_id)}
        <div class="alert alert-info">
            <strong>
                {l s='Run' d='Modules.Cpbsync.Admin'} #{$log_id}
            </strong>

            <p>
                {l s='This synchronization was recorded in the history.' d='Modules.Cpbsync.Admin'}
            </p>
        </div>
    {/if}

    <div class="row">

        <div class="col-md-2">
            <div class="well text-center">
                <strong>{l s='Total' d='Modules.Cpbsync.Admin'}</strong>
                <br>
                {$result.total}
            </div>
        </div>

        <div class="col-md-2">
            <div class="alert alert-success text-center">
                <strong>{l s='Created' d='Modules.Cpbsync.Admin'}</strong><br>
                <span style="font-size: 24px;">
                    {$result.created}
                </span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="alert alert-info text-center">
                <strong>{l s='Updated' d='Modules.Cpbsync.Admin'}</strong><br>
                <span style="font-size: 24px;">
            {$result.updated}
        </span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="alert alert-warning text-center">
                <strong>{l s='Skipped' d='Modules.Cpbsync.Admin'}</strong><br>
                <span style="font-size: 24px;">
                    {$result.skipped}
                </span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="alert alert-danger text-center">
                <strong>{l s='Errors' d='Modules.Cpbsync.Admin'}</strong><br>
                <span style="font-size: 24px;">
                    {$result.errors}
                </span>
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>{l s='Reference' d='Modules.Cpbsync.Admin'}</th>
            <th>{l s='Status' d='Modules.Cpbsync.Admin'}</th>
            <th>{l s='PrestaShop ID' d='Modules.Cpbsync.Admin'}</th>
            <th>{l s='Detail' d='Modules.Cpbsync.Admin'}</th>
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
                            {l s='Created' d='Modules.Cpbsync.Admin'}
                        </span>
                    {elseif $item.status === 'updated'}
                        <span class="label label-info">
                            {l s='Updated' d='Modules.Cpbsync.Admin'}
                        </span>
                    {elseif $item.status === 'skipped'}
                        <span class="label label-warning">
                            {l s='Skipped' d='Modules.Cpbsync.Admin'}
                        </span>
                    {else}
                        <span class="label label-danger">
                            {l s='Errors' d='Modules.Cpbsync.Admin'}
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
            {l s='Back to mapping' d='Modules.Cpbsync.Admin'}
        </a>
    </div>
</div>