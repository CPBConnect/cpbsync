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

                        <tr
                                data-field="{$header|escape:'htmlall':'UTF-8'}"
                                data-config="{$saved_config_json[$header]|default:'{}'|escape:'htmlall':'UTF-8'}"
                        >

                            <td>
                                <strong>
                                    {$header|escape:'htmlall':'UTF-8'}
                                </strong>
                            </td>

                            <td>

                                <select
                                        name="mapping[{$header|escape:'htmlall':'UTF-8'}]"
                                        class="form-control js-target"
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

                                <select
                                        name="transformation[{$header|escape:'htmlall':'UTF-8'}]"
                                        class="form-control js-transformation"
                                >

                                    <option value="">
                                        {l s='-- None --' d='Modules.Cpbsync.Admin'}
                                    </option>

                                    {foreach from=$transform_options item=option}
                                        <option
                                                value="{$option.name|escape:'htmlall':'UTF-8'}"
                                                data-targets="{$option.targets|escape:'htmlall':'UTF-8'}"
                                                {if $selected_transformation == $option.name}selected{/if}
                                        >
                                            {$option.label|escape:'htmlall':'UTF-8'}
                                        </option>
                                    {/foreach}

                                </select>

                                <div
                                        class="js-transform-config"
                                        style="margin-top: 10px;"
                                ></div>

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

{*
    Configuración de cada transformación, una sola vez por página. El
    script copia la que corresponda en la fila que la pida, así que no
    se repite por cada campo del catálogo.
*}
<div id="cpbsync-transform-templates" style="display: none;">

    {foreach from=$transform_options item=option}

        {if !empty($option.fields)}

            <div data-transform-template="{$option.name|escape:'htmlall':'UTF-8'}">

                {foreach from=$option.fields item=field}

                    <div class="form-group">

                        <label>
                            {$field.label|escape:'htmlall':'UTF-8'}
                        </label>

                        {if $field.type == 'select'}

                            <select
                                    class="form-control"
                                    data-config-key="{$field.name|escape:'htmlall':'UTF-8'}"
                                    data-config-name="transformation_config[__FIELD__][{$field.name|escape:'htmlall':'UTF-8'}]"
                            >
                                {foreach from=$field.options item=field_option}
                                    <option value="{$field_option.value|escape:'htmlall':'UTF-8'}">
                                        {$field_option.label|escape:'htmlall':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>

                        {elseif $field.type == 'textarea'}

                            <textarea
                                    class="form-control"
                                    rows="3"
                                    data-config-key="{$field.name|escape:'htmlall':'UTF-8'}"
                                    data-config-name="transformation_config[__FIELD__][{$field.name|escape:'htmlall':'UTF-8'}]"
                            ></textarea>

                        {else}

                            <input
                                    type="text"
                                    class="form-control"
                                    data-config-key="{$field.name|escape:'htmlall':'UTF-8'}"
                                    data-config-name="transformation_config[__FIELD__][{$field.name|escape:'htmlall':'UTF-8'}]"
                            >

                        {/if}

                        {if $field.hint}
                            <p class="help-block">
                                {$field.hint|escape:'htmlall':'UTF-8'}
                            </p>
                        {/if}

                    </div>

                {/foreach}

            </div>

        {/if}

    {/foreach}

</div>

<script>
    (function () {

        var templates = document.getElementById('cpbsync-transform-templates');

        function findTemplate(name) {
            if (!templates || !name) {
                return null;
            }

            return templates.querySelector(
                '[data-transform-template="' + name + '"]'
            );
        }

        // Deja visible sólo lo que se puede aplicar al campo destino.
        function filterTransformations(row) {
            var target = row.querySelector('.js-target');
            var select = row.querySelector('.js-transformation');

            if (!target || !select) {
                return;
            }

            select.querySelectorAll('option').forEach(function (option) {
                var targets = (option.dataset.targets || '')
                    .split(',')
                    .filter(Boolean);

                // La opción ya elegida nunca se oculta: si el mapeo se
                // guardó con otra versión no se pierde al reenviar.
                var allowed = option.value === ''
                    || targets.length === 0
                    || targets.indexOf(target.value) !== -1
                    || option.selected;

                option.hidden = !allowed;
                option.disabled = !allowed;
            });

            refreshConfig(row);
        }

        // Copia la configuración de la transformación elegida.
        function refreshConfig(row) {
            var select = row.querySelector('.js-transformation');
            var box = row.querySelector('.js-transform-config');

            if (!select || !box) {
                return;
            }

            var config = {};

            try {
                config = JSON.parse(row.dataset.config || '{}') || {};
            } catch (error) {
                config = {};
            }

            box.innerHTML = '';

            var source = findTemplate(select.value);

            if (!source) {
                return;
            }

            var clone = source.cloneNode(true);

            clone.style.display = '';

            clone.querySelectorAll('[data-config-name]').forEach(
                function (element) {
                    var key = element.dataset.configKey || '';

                    element.name = element.dataset.configName
                        .replace('__FIELD__', row.dataset.field || '');

                    if (Object.prototype.hasOwnProperty.call(config, key)) {
                        element.value = config[key];
                    }
                }
            );

            box.appendChild(clone);
        }

        document.addEventListener('change', function (event) {
            var row = event.target.closest('tr[data-field]');

            if (!row) {
                return;
            }

            if (event.target.classList.contains('js-target')) {
                filterTransformations(row);
            }

            if (event.target.classList.contains('js-transformation')) {
                refreshConfig(row);
            }
        });

        document.querySelectorAll('tr[data-field]').forEach(
            function (row) {
                filterTransformations(row);
            }
        );
    })();
</script>
