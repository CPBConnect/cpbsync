<div class="panel">
    <h3>
        {l s='Importar productos' mod='cpbsync'}
    </h3>

    <form
            id="cpbsync-import-form"
            method="post"
            action=""
            enctype="multipart/form-data"
    >
        <input
                type="hidden"
                name="cpbsync_action"
                value="upload_import"
        >

        <div class="form-group">
            <label for="id_source">
                {l s='Fuente' mod='cpbsync'}
            </label>

            <select
                    name="id_source"
                    id="id_source"
                    class="form-control"
                    required
            >
                <option value="">
                    {l s='Selecciona una fuente' mod='cpbsync'}
                </option>

                {foreach from=$sources item=source}
                    <option value="{$source.id_source|intval}">
                        {$source.name|escape:'htmlall':'UTF-8'}
                    </option>
                {/foreach}
            </select>
        </div>

        <div class="form-group">
            <label for="import_file">
                {l s='Archivo CSV' mod='cpbsync'}
            </label>

            <input
                    type="file"
                    name="import_file"
                    id="import_file"
                    class="form-control"
                    accept=".csv,text/csv"
                    required
            >
        </div>
        <a
                href="{$back_url}"
                class="btn btn-default"
        >
            <i class="icon-arrow-left"></i>
            Volver
        </a>
        <button
                type="submit"
                class="btn btn-primary"
                id="cpbsync-import-submit"
        >
            {l s='Subir e iniciar importación' mod='cpbsync'}
        </button>
    </form>

    <div
            id="cpbsync-import-progress"
            style="display:none; margin-top:20px;"
    >
        <h4>
            {l s='Procesando importación' mod='cpbsync'}
        </h4>

        <div class="progress">
            <div
                    id="cpbsync-progress-bar"
                    class="progress-bar"
                    role="progressbar"
                    style="width:0%;"
            >
                0%
            </div>
        </div>

        <p id="cpbsync-progress-text">
            0 / 0
        </p>

        <p>
            <strong>
                {l s='Exitosos:' mod='cpbsync'}
            </strong>

            <span id="cpbsync-success">
                0
            </span>
        </p>

        <p>
            <strong>
                {l s='Errores:' mod='cpbsync'}
            </strong>

            <span id="cpbsync-errors">
                0
            </span>
        </p>

        <div
                id="cpbsync-import-result"
                style="margin-top:15px;"
        ></div>
    </div>
</div>

<script>
    window.cpbsyncImportId = {$import_id|default:0|intval};
    window.cpbsyncProcessUrl = '{$process_url|escape:'javascript'}';
</script>

<script src="{$module_dir}views/js/import.js"></script>