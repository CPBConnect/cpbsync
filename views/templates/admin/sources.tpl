<div class="panel">

    <div class="panel-heading">
        <i class="icon-refresh"></i>
        {l s='CPB Sync' d='Modules.Cpbsync.Admin'}
    </div>

    <div class="panel-body">

        <div class="row">
            <div class="col-md-8">

                <h3>
                    {l s='Data sources' d='Modules.Cpbsync.Admin'}
                </h3>

                <p class="text-muted">
                    {l s='Connect suppliers, ERP systems and other product sources with PrestaShop.' d='Modules.Cpbsync.Admin'}
                </p>

            </div>

            <div class="col-md-4 text-right">
                <a
                        href="{$import_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-primary"
                >
                    {l s='Import CSV' d='Modules.Cpbsync.Admin'}
                </a>

                {if $monitor_url}
                    <a
                            href="{$monitor_url|escape:'htmlall':'UTF-8'}"
                            class="btn btn-default"
                    >
                        <i class="icon-dashboard"></i>
                        {l s='Monitoring' d='Modules.Cpbsync.Admin'}
                    </a>
                {/if}

                <a
                        href="{$history_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-default"
                >
                    <i class="icon-time"></i>
                    {l s='History' d='Modules.Cpbsync.Admin'}
                </a>
                <a
                        href="{$source_form_url|escape:'htmlall':'UTF-8'}"
                        class="btn btn-primary"
                >
                    <i class="icon-plus"></i>
                    {l s='Add source' d='Modules.Cpbsync.Admin'}
                </a>

            </div>
        </div>

        <hr>

        {if empty($sources)}

            <div class="alert alert-info">
                <i class="icon-info-circle"></i>
                {l s='You have no sources configured yet.' d='Modules.Cpbsync.Admin'}
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
                                {l s='Type:' d='Modules.Cpbsync.Admin'}
                            </strong>

                            {$source.type|escape:'htmlall':'UTF-8'}
                        </p>

                        <p>
                            <strong>
                                {l s='URL:' d='Modules.Cpbsync.Admin'}
                            </strong>

                            {$source.url|escape:'htmlall':'UTF-8'}
                        </p>

                        <p>
                            <strong>
                                {l s='Frequency:' d='Modules.Cpbsync.Admin'}
                            </strong>

                            {$source.frequency_label|default:$source.frequency|escape:'htmlall':'UTF-8'}

                            {if $source.next_run}
                                &mdash;

                                <strong>
                                    {l s='Next run:' d='Modules.Cpbsync.Admin'}
                                </strong>

                                {$source.next_run|escape:'htmlall':'UTF-8'}
                            {/if}
                        </p>

                        <p>
                            <strong>
                                {l s='Status:' d='Modules.Cpbsync.Admin'}
                            </strong>

                            {if $source.active}
                                <span class="label label-success">
                                    {l s='Active' d='Modules.Cpbsync.Admin'}
                                </span>
                            {else}
                                <span class="label label-default">
                                    {l s='Inactive' d='Modules.Cpbsync.Admin'}
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
                                {l s='Test connection' d='Modules.Cpbsync.Admin'}
                            </a>

                            <a
                                    href="{$source.map_url|escape:'htmlall':'UTF-8'}"
                                    class="btn btn-default"
                            >
                                <i class="icon-random"></i>
                                {l s='Map fields' d='Modules.Cpbsync.Admin'}
                            </a>

                            <a
                                    href="{$source.edit_url|escape:'htmlall':'UTF-8'}"
                                    class="btn btn-default"
                            >
                                <i class="icon-edit"></i>
                                {l s='Edit' d='Modules.Cpbsync.Admin'}
                            </a>

                            <form
                                    method="post"
                                    action="{$source.delete_url|escape:'htmlall':'UTF-8'}"
                                    style="display:inline;"
                                    onsubmit="return confirm('{l s='Are you sure you want to delete this source?' d='Modules.Cpbsync.Admin'}');"
                            >
                                <button
                                        type="submit"
                                        class="btn btn-danger"
                                >
                                    <i class="icon-trash"></i>
                                    {l s='Delete' d='Modules.Cpbsync.Admin'}
                                </button>
                            </form>

                        </div>

                    </div>

                </div>

            {/foreach}

        {/if}

    </div>

</div>