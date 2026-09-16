<div class="panel">
    <div class="panel-heading">
        <i class="icon-eye"></i>
        Dry Run
    </div>

    <div class="alert alert-info">
        <strong>{l s='Preview' d='Modules.Cpbsync.Admin'}</strong>
        <p>
            {l s='The first 5 products from the source are shown.' d='Modules.Cpbsync.Admin'}
        </p>
        <strong>{l s='No product was modified in PrestaShop.' d='Modules.Cpbsync.Admin'}</strong>
    </div>

    {foreach from=$products item=product}

        <div class="panel" style="margin-bottom: 20px;">

            <div class="panel-heading">
                {l s='Product' d='Modules.Cpbsync.Admin'} {$product.number}

                {if $product.valid}
                    <span class="label label-success pull-right">
                        ✓ {l s='Valid' d='Modules.Cpbsync.Admin'}
                    </span>
                {else}
                    <span class="label label-danger pull-right">
                        ✗ {l s='With errors' d='Modules.Cpbsync.Admin'}
                    </span>
                {/if}
            </div>

            {if !$product.valid}
                <div class="alert alert-danger">
                    <strong>{l s='Errors:' d='Modules.Cpbsync.Admin'}</strong>

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
                    <th>{l s='PrestaShop field' d='Modules.Cpbsync.Admin'}</th>
                    <th>{l s='Original' d='Modules.Cpbsync.Admin'}</th>
                    <th>{l s='Result' d='Modules.Cpbsync.Admin'}</th>
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
                                    {l s='Changed' d='Modules.Cpbsync.Admin'}
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
            {l s='Back to mapping' d='Modules.Cpbsync.Admin'}
        </a>
    </div>
</div>