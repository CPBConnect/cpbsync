<div class="panel">

    <div class="panel-heading">
        <i class="icon-random"></i>
        {l s='Map fields' d='Modules.Cpbsync.Admin'}
    </div>

    <div class="panel-body">

        <div class="alert alert-info">
            {l s='Assign each source field to the matching PrestaShop field.' d='Modules.Cpbsync.Admin'}
        </div>

        <form
                method="post"
                action="{$save_mapping_url|escape:'htmlall':'UTF-8'}"
        >

            <input
                    type="hidden"
                    name="id_source"
                    value="{$source.id_source|intval}"
            >

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead>
                    <tr>
                        <th>
                            {l s='Supplier field' d='Modules.Cpbsync.Admin'}
                        </th>
                        <th>
                            {l s='PrestaShop field' d='Modules.Cpbsync.Admin'}
                        </th>
                        <th>
                            {l s='Transformation' d='Modules.Cpbsync.Admin'}
                        </th>
                    </tr>
                    </thead>

                    <tbody>

                    {foreach from=$headers item=header}

                        {assign var="suggested_target" value=""}

                        {if $header|lower == 'sku'}
                            {assign var="suggested_target" value="reference"}
                        {elseif $header|lower == 'name'}
                            {assign var="suggested_target" value="name"}
                        {elseif $header|lower == 'description'}
                            {assign var="suggested_target" value="description"}
                        {elseif $header|lower == 'price'}
                            {assign var="suggested_target" value="price"}
                        {elseif $header|lower == 'stock'}
                            {assign var="suggested_target" value="quantity"}
                        {elseif $header|lower == 'category'}
                            {assign var="suggested_target" value="category"}
                        {elseif $header|lower == 'brand'}
                            {assign var="suggested_target" value="manufacturer"}
                        {elseif $header|lower == 'image'}
                            {assign var="suggested_target" value="image"}
                        {elseif $header|lower == 'ean'}
                            {assign var="suggested_target" value="ean13"}
                        {/if}

                        {assign var="selected_target" value=""}

                        {if isset($saved_mappings[$header])}
                            {assign var="selected_target" value=$saved_mappings[$header]}
                        {/if}

                        {assign var="selected_transformation" value=""}

                        {if isset($saved_transformations[$header])}
                            {assign var="selected_transformation" value=$saved_transformations[$header]}
                        {/if}

                        <tr>

                            <td>
                                <strong>
                                    {$header|escape:'htmlall':'UTF-8'}
                                </strong>
                            </td>

                            <td>

                                <select
                                        name="mapping[{$header|escape:'htmlall':'UTF-8'}]"
                                        class="form-control"
                                >

                                    <option value="">
                                        {l s='-- Do not map --' d='Modules.Cpbsync.Admin'}
                                    </option>

                                    <option
                                            value="reference"
                                            {if $selected_target == 'reference'}selected{/if}
                                    >
                                        reference
                                    </option>

                                    <option
                                            value="name"
                                            {if $selected_target == 'name'}selected{/if}
                                    >
                                        name
                                    </option>

                                    <option
                                            value="description"
                                            {if $selected_target == 'description'}selected{/if}
                                    >
                                        description
                                    </option>

                                    <option
                                            value="price"
                                            {if $selected_target == 'price'}selected{/if}
                                    >
                                        price
                                    </option>

                                    <option
                                            value="quantity"
                                            {if $selected_target == 'quantity'}selected{/if}
                                    >
                                        quantity
                                    </option>

                                    <option
                                            value="category"
                                            {if $selected_target == 'category'}selected{/if}
                                    >
                                        category
                                    </option>

                                    <option
                                            value="manufacturer"
                                            {if $selected_target == 'manufacturer'}selected{/if}
                                    >
                                        manufacturer
                                    </option>

                                    <option
                                            value="image"
                                            {if $selected_target == 'image'}selected{/if}
                                    >
                                        image
                                    </option>

                                    <option
                                            value="ean13"
                                            {if $selected_target == 'ean13'}selected{/if}
                                    >
                                        ean13
                                    </option>

                                </select>

                            </td>
                            <td>
                                {if $selected_target == 'price'}
                                    <select
                                            name="transformation[{$header|escape:'htmlall':'UTF-8'}]"
                                            class="form-control"
                                    >
                                        <option value="">
                                            {l s='-- None --' d='Modules.Cpbsync.Admin'}
                                        </option>

                                        <option
                                                value="normalize_price"
                                                {if $selected_transformation == 'normalize_price'}selected{/if}
                                        >
                                            {l s='Normalize price' d='Modules.Cpbsync.Admin'}
                                        </option>
                                    </select>
                                {elseif $selected_target == 'quantity'}
                                    <select
                                            name="transformation[{$header|escape:'htmlall':'UTF-8'}]"
                                            class="form-control"
                                    >
                                        <option value="">
                                            {l s='-- None --' d='Modules.Cpbsync.Admin'}
                                        </option>

                                        <option
                                                value="normalize_stock"
                                                {if $selected_transformation == 'normalize_stock'}selected{/if}
                                        >
                                            {l s='Normalize stock' d='Modules.Cpbsync.Admin'}
                                        </option>
                                    </select>
                                {elseif $selected_target == 'name'
                                || $selected_target == 'description'
                                || $selected_target == 'manufacturer'}
                                    <select
                                            name="transformation[{$header|escape:'htmlall':'UTF-8'}]"
                                            class="form-control js-transformation"
                                    >
                                        <option value="">
                                            {l s='-- None --' d='Modules.Cpbsync.Admin'}
                                        </option>

                                        <option
                                                value="normalize_text"
                                                {if $selected_transformation == 'normalize_text'}selected{/if}
                                        >
                                            {l s='Normalize text' d='Modules.Cpbsync.Admin'}
                                        </option>

                                        <option
                                                value="replace_text"
                                                {if $selected_transformation == 'replace_text'}selected{/if}
                                        >
                                            {l s='Replace text' d='Modules.Cpbsync.Admin'}
                                        </option>
                                    </select>

                                    <div
                                            class="row js-replace-config"
                                            style="margin-top: 10px; {if $selected_transformation != 'replace_text'}display: none;{/if}"
                                    >

                                        <div class="col-md-6">
                                            <label>
                                                {l s='Search' d='Modules.Cpbsync.Admin'}
                                            </label>

                                            <input
                                                    type="text"
                                                    name="transformation_search[{$header|escape:'htmlall':'UTF-8'}]"
                                                    class="form-control"
                                                    placeholder="Text to search"
                                                    value="{$saved_transformation_configs[$header]['search']|default:''|escape:'htmlall':'UTF-8'}"
                                            >
                                        </div>

                                        <div class="col-md-6">
                                            <label>
                                                {l s='Replace with' d='Modules.Cpbsync.Admin'}
                                            </label>

                                            <input
                                                    type="text"
                                                    name="transformation_replace[{$header|escape:'htmlall':'UTF-8'}]"
                                                    class="form-control"
                                                    placeholder="New text"
                                                    value="{$saved_transformation_configs[$header]['replace']|default:''|escape:'htmlall':'UTF-8'}"
                                            >
                                        </div>

                                    </div>

                                {else}
                                    <span class="text-muted">
                                        {l s='Not available' d='Modules.Cpbsync.Admin'}
                                    </span>
                                {/if}

                            </td>
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
                {l s='Back' d='Modules.Cpbsync.Admin'}
            </a>

            <button
                    type="submit"
                    class="btn btn-primary"
            >
                <i class="icon-save"></i>
                {l s='Save mapping' d='Modules.Cpbsync.Admin'}
            </button>

            <a href="{$dry_run_url}" class="btn btn-outline-primary">
                {l s='Run Dry Run' d='Modules.Cpbsync.Admin'}
            </a>

            <a href="{$sync_url}" class="btn btn-primary">
                <i class="icon-refresh"></i>
                {l s='Run Sync' d='Modules.Cpbsync.Admin'}
            </a>

        </form>

    </div>

</div>

<script>
    document.addEventListener('change', function (event) {

        if (!event.target.classList.contains('js-transformation')) {
            return;
        }

        const select = event.target;

        const config = select.nextElementSibling;

        if (!config || !config.classList.contains('js-replace-config')) {
            return;
        }

        if (select.value === 'replace_text') {
            config.style.display = '';
        } else {
            config.style.display = 'none';
        }
    });
</script>