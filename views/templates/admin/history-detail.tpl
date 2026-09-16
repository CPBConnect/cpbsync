<div class="panel">

    <div class="panel-heading">
        <i class="icon-search"></i>
        {l s='Synchronization detail' d='Modules.Cpbsync.Admin'}
    </div>

    <div class="alert alert-info">

        <strong>
            {l s='Run' d='Modules.Cpbsync.Admin'} #{$log.id_log}
        </strong>

        <p>
            {l s='Date:' d='Modules.Cpbsync.Admin'}
            {$log.date_add|escape:'htmlall':'UTF-8'}
        </p>

        <p>
            {l s='Source:' d='Modules.Cpbsync.Admin'}

            {if $source}
                {$source.name|escape:'htmlall':'UTF-8'}
            {else}
                {l s='Deleted source' d='Modules.Cpbsync.Admin'}
            {/if}
        </p>

        <p>
            {l s='Duration:' d='Modules.Cpbsync.Admin'}

            {if $log.duration !== null}
                {$log.duration} s
            {else}
                -
            {/if}

            &mdash;

            {l s='Memory:' d='Modules.Cpbsync.Admin'}

            {if $log.memory !== null}
                {$log.memory} MB
            {else}
                -
            {/if}
        </p>

        {if !empty($phases)}
            <ul>
                {foreach from=$phases item=phase}
                    <li>
                        {$phase.name|escape:'htmlall':'UTF-8'}:
                        <strong>{$phase.seconds} s</strong>
                    </li>
                {/foreach}
            </ul>
        {/if}

    </div>

    <div class="row">

        <div class="col-md-2">
            <div class="well text-center">
                <strong>{l s='Total' d='Modules.Cpbsync.Admin'}</strong>
                <br>
                {$log.total}
            </div>
        </div>

        <div class="col-md-2">
            <div class="well text-center">
                <strong>{l s='Created' d='Modules.Cpbsync.Admin'}</strong>
                <br>
                {$log.created}
            </div>
        </div>

        <div class="col-md-2">
            <div class="well text-center">
                <strong>{l s='Updated' d='Modules.Cpbsync.Admin'}</strong>
                <br>
                {$log.updated}
            </div>
        </div>

        <div class="col-md-2">
            <div class="well text-center">
                <strong>{l s='Unchanged' d='Modules.Cpbsync.Admin'}</strong>
                <br>
                {$log.skipped}
            </div>
        </div>

        <div class="col-md-2">
            <div class="well text-center">
                <strong>{l s='Errors' d='Modules.Cpbsync.Admin'}</strong>
                <br>
                {$log.errors}
            </div>
        </div>

    </div>

</div>


<div class="panel">

    <div class="panel-heading">
        {l s='Processed products' d='Modules.Cpbsync.Admin'}
    </div>

    {if empty($details)}

        <div class="alert alert-info">
            {l s='No details available.' d='Modules.Cpbsync.Admin'}
        </div>

    {else}

        {if $items_total > $items_shown}
            <div class="alert alert-warning">
                {l s='Only the first %shown% of %total% records are stored. The rest are summarised in the counters above.' sprintf=['%shown%' => $items_shown, '%total%' => $items_total] d='Modules.Cpbsync.Admin'}
            </div>
        {/if}

        <table class="table table-striped">

            <thead>
            <tr>
                <th>{l s='Reference' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Status' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Product ID' d='Modules.Cpbsync.Admin'}</th>
                <th>{l s='Errors' d='Modules.Cpbsync.Admin'}</th>
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
                                {l s='Created' d='Modules.Cpbsync.Admin'}
                            </span>

                        {elseif $item.status == 'updated'}

                            <span class="label label-info">
                                {l s='Updated' d='Modules.Cpbsync.Admin'}
                            </span>

                        {elseif $item.status == 'skipped'}

                            <span class="label label-default">
                                {l s='Unchanged' d='Modules.Cpbsync.Admin'}
                            </span>

                        {else}

                            <span class="label label-danger">
                                {l s='Error' d='Modules.Cpbsync.Admin'}
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
            {l s='Back to history' d='Modules.Cpbsync.Admin'}
        </a>

    </div>

</div>
