<div class="panel">

    <div class="panel-heading">
        <i class="icon-dashboard"></i>
        {l s='Monitoring' d='Modules.Cpbsync.Admin'}
    </div>

    <div class="panel-body">

        <div class="row">

            <div class="col-md-6">
                <div class="btn-group">
                    {foreach from=$periods item=period}
                        <a
                                href="{$period.url|escape:'htmlall':'UTF-8'}"
                                class="btn btn-default {if $period.active}active{/if}"
                        >
                            {$period.label|escape:'htmlall':'UTF-8'}
                        </a>
                    {/foreach}
                </div>
            </div>

            <div class="col-md-6 text-right">
                <a
                        href="{$history_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-default"
                >
                    <i class="icon-time"></i>
                    {l s='History' d='Modules.Cpbsync.Admin'}
                </a>
                <a
                        href="{$back_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-default"
                >
                    <i class="icon-arrow-left"></i>
                    {l s='Back to sources' d='Modules.Cpbsync.Admin'}
                </a>
            </div>

        </div>

        <hr>

        <div class="row">

            <div class="col-md-3">
                <div class="well text-center">
                    <strong>{l s='Runs' d='Modules.Cpbsync.Admin'}</strong>
                    <h3>{$summary.runs}</h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="well text-center">
                    <strong>{l s='Products processed' d='Modules.Cpbsync.Admin'}</strong>
                    <h3>{$summary.products}</h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="well text-center">
                    <strong>{l s='Errors' d='Modules.Cpbsync.Admin'}</strong>
                    <h3>
                        {if $summary.errors > 0}
                            <span class="label label-danger">
                                {$summary.errors}
                            </span>
                        {else}
                            {$summary.errors}
                        {/if}
                    </h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="well text-center">
                    <strong>{l s='Average duration' d='Modules.Cpbsync.Admin'}</strong>
                    <h3>

                        {if $summary.runs > 0}
                            {$summary.avg_duration} s
                        {else}
                            -
                        {/if}

                    </h3>
                </div>
            </div>

        </div>

        <div class="row">

            <div class="col-md-3">
                <div class="well text-center">
                    <strong>{l s='Created' d='Modules.Cpbsync.Admin'}</strong>
                    <br>
                    {$summary.created}
                </div>
            </div>

            <div class="col-md-3">
                <div class="well text-center">
                    <strong>{l s='Updated' d='Modules.Cpbsync.Admin'}</strong>
                    <br>
                    {$summary.updated}
                </div>
            </div>

            <div class="col-md-3">
                <div class="well text-center">
                    <strong>{l s='Unchanged' d='Modules.Cpbsync.Admin'}</strong>
                    <br>
                    {$summary.skipped}
                </div>
            </div>

            <div class="col-md-3">
                <div class="well text-center">
                    <strong>{l s='Longest run' d='Modules.Cpbsync.Admin'}</strong>
                    <br>

                    {if $summary.runs > 0}
                        {$summary.max_duration} s
                    {else}
                        -
                    {/if}

                </div>
            </div>

        </div>

        <p class="text-muted">

            {l s='Last run:' d='Modules.Cpbsync.Admin'}

            {if $summary.last_run}
                {$summary.last_run|escape:'htmlall':'UTF-8'}
            {else}
                {l s='Never' d='Modules.Cpbsync.Admin'}
            {/if}

        </p>

    </div>

</div>


<div class="row">

    <div class="col-md-7">

        <div class="panel">

            <div class="panel-heading">
                {l s='Most frequent errors' d='Modules.Cpbsync.Admin'}
            </div>

            {if empty($top_errors)}

                <div class="alert alert-success">
                    {l s='No errors were recorded in this period.' d='Modules.Cpbsync.Admin'}
                </div>

            {else}

                <table class="table table-striped">

                    <thead>
                    <tr>
                        <th>{l s='Error' d='Modules.Cpbsync.Admin'}</th>
                        <th class="text-right">
                            {l s='Times' d='Modules.Cpbsync.Admin'}
                        </th>
                    </tr>
                    </thead>

                    <tbody>

                    {foreach from=$top_errors key=message item=times}
                        <tr>
                            <td>{$message|escape:'htmlall':'UTF-8'}</td>
                            <td class="text-right">
                                <span class="label label-warning">
                                    {$times}
                                </span>
                            </td>
                        </tr>
                    {/foreach}

                    </tbody>

                </table>

            {/if}

        </div>

    </div>

    <div class="col-md-5">

        <div class="panel">

            <div class="panel-heading">
                {l s='History storage' d='Modules.Cpbsync.Admin'}
            </div>

            <div class="panel-body">

                <p>
                    {l s='Stored runs:' d='Modules.Cpbsync.Admin'}
                    <strong>{$storage.rows}</strong>
                </p>

                <p>
                    {l s='Stored details:' d='Modules.Cpbsync.Admin'}
                    <strong>{$storage.megabytes} MB</strong>
                </p>

                <p class="text-muted">
                    {l s='The history only stores the summary of each run plus a limited sample of products, so it stays small even with large catalogs.' d='Modules.Cpbsync.Admin'}
                </p>

            </div>

            <div class="panel-footer">

                <a
                        href="{$purge_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-warning"
                        onclick="return confirm('{l s='Delete the stored runs older than the retention period?' d='Modules.Cpbsync.Admin' js=1}');"
                >
                    <i class="icon-trash"></i>
                    {l s='Delete old runs' d='Modules.Cpbsync.Admin'}
                </a>

                <span class="text-muted">
                    {l s='Keeps the last %days% days.' sprintf=['%days%' => $retention_days] d='Modules.Cpbsync.Admin'}
                </span>

            </div>

        </div>

    </div>

</div>


<div class="panel">

    <div class="panel-heading">
        {l s='Recent runs' d='Modules.Cpbsync.Admin'}
    </div>

    {if empty($logs)}

        <div class="alert alert-info">
            {l s='There are no runs stored yet.' d='Modules.Cpbsync.Admin'}
        </div>

    {else}

        <table class="table table-striped">

            <thead>
            <tr>
                <th>{l s='Date' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Source' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Trigger' d='Modules.Cpbsync.Admin'}</th>
                <th class="text-right">
                    {l s='Total' d='Modules.Cpbsync.Admin'}
                </th>
                <th class="text-right">
                    {l s='Errors' d='Modules.Cpbsync.Admin'}
                </th>
                <th class="text-right">
                    {l s='Duration' d='Modules.Cpbsync.Admin'}
                </th>
                <th class="text-right">
                    {l s='Memory' d='Modules.Cpbsync.Admin'}
                </th>
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
                        {$log.execution_type|escape:'htmlall':'UTF-8'}
                    </td>

                    <td class="text-right">
                        {$log.total}
                    </td>

                    <td class="text-right">

                        {if $log.errors > 0}
                            <span class="label label-danger">
                                {$log.errors}
                            </span>
                        {else}
                            {$log.errors}
                        {/if}

                    </td>

                    <td class="text-right">

                        {if $log.duration !== null}
                            {$log.duration} s
                        {else}
                            -
                        {/if}

                    </td>

                    <td class="text-right">

                        {if $log.memory !== null}
                            {$log.memory} MB
                        {else}
                            -
                        {/if}

                    </td>

                    <td class="text-right">
                        <a
                                href="{$log.detail_url|escape:'htmlall':'UTF-8'}"
                                class="btn btn-default btn-xs"
                        >
                            {l s='Detail' d='Modules.Cpbsync.Admin'}
                        </a>
                    </td>

                </tr>

            {/foreach}

            </tbody>

        </table>

    {/if}

</div>
