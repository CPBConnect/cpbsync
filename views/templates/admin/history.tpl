<div class="panel">

    <div class="panel-heading">
        <i class="icon-time"></i>
        {l s='Synchronization history' d='Modules.Cpbsync.Admin'}
    </div>

    {if empty($logs)}

        <div class="alert alert-info">
            {l s='There are no synchronizations recorded yet.' d='Modules.Cpbsync.Admin'}
        </div>

    {else}

        <table class="table table-striped">

            <thead>
            <tr>
                <th>{l s='Date' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Source' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Total' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Created' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Updated' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Unchanged' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Errors' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Status' d='Modules.Cpbsync.Admin'}</th>
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
                                {l s='Success' d='Modules.Cpbsync.Admin'}
                            </span>

                        {elseif $log.status == 'warning'}

                            <span class="label label-warning">
                                {l s='Warning' d='Modules.Cpbsync.Admin'}
                            </span>

                        {else}

                            <span class="label label-danger">
                                {l s='Error' d='Modules.Cpbsync.Admin'}
                            </span>

                        {/if}

                    </td>

                    <td class="text-right">

                        <a
                                href="{$log.detail_url}"
                                class="btn btn-default btn-sm"
                        >
                            <i class="icon-search"></i>
                            {l s='View detail' d='Modules.Cpbsync.Admin'}
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
            {l s='Back' d='Modules.Cpbsync.Admin'}
        </a>

    </div>

</div>
