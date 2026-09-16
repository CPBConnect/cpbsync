<div class="panel">
    <h3>
        {l s='Import products' d='Modules.Cpbsync.Admin'}
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
                {l s='Source' d='Modules.Cpbsync.Admin'}
            </label>

            <select
                    name="id_source"
                    id="id_source"
                    class="form-control"
                    required
            >
                <option value="">
                    {l s='Select a source' d='Modules.Cpbsync.Admin'}
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
                {l s='CSV file' d='Modules.Cpbsync.Admin'}
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
            {l s='Back' d='Modules.Cpbsync.Admin'}
        </a>
        <button
                type="submit"
                class="btn btn-primary"
                id="cpbsync-import-submit"
        >
            {l s='Upload and start import' d='Modules.Cpbsync.Admin'}
        </button>
    </form>

    <div
            id="cpbsync-import-progress"
            style="display:none; margin-top:20px;"
    >
        <h4>
            {l s='Processing import' d='Modules.Cpbsync.Admin'}
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
                {l s='Successful:' d='Modules.Cpbsync.Admin'}
            </strong>

            <span id="cpbsync-success">
                0
            </span>
        </p>

        <p>
            <strong>
                {l s='Errors:' d='Modules.Cpbsync.Admin'}
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

    window.cpbsyncMessages = {
        uploading: '{$import_messages.uploading|escape:'javascript'}',
        uploaded: '{$import_messages.uploaded|escape:'javascript'}',
        completed: '{$import_messages.completed|escape:'javascript'}',
        failed: '{$import_messages.failed|escape:'javascript'}',
        failedTitle: '{$import_messages.failedTitle|escape:'javascript'}'
    };
</script>

<script src="{$module_dir}views/js/import.js"></script>