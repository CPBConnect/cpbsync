<?php

namespace CPBConnect\Presentation\Admin\Handler;

use CPBConnect\Application\Form\DescribedFields;
use CPBConnect\Application\Schedule\ScheduleFactory;
use CPBConnect\Application\Schedule\ScheduleRegistry;
use CPBConnect\Application\Schedule\ScheduleSummary;
use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use CPBConnect\Application\Source\SourceService;
use CPBConnect\Application\Source\SourceValidator;
use CPBConnect\Presentation\Admin\AdminLinkBuilder;
use CPBConnect\Presentation\Admin\AdminShellInterface;
use Tools;

/**
 * Acciones de administración sobre las fuentes de datos.
 */
class SourceHandler
{
    private const PREVIEW_ROWS = 5;

    private ScheduleRegistry $schedules;
    private ScheduleSummary $summary;

    public function __construct(
        private AdminShellInterface $shell,
        private AdminLinkBuilder $links,
        private SourceService $sources,
        private SourceValidator $validator,
        private SourceReaderRegistry $readers,
        private ?string $monitorUrl = null,
        ?ScheduleRegistry $schedules = null,
        ?ScheduleSummary $summary = null
    ) {
        $this->schedules = $schedules ?? ScheduleFactory::create();

        $this->summary = $summary ?? new ScheduleSummary(
            $this->schedules
        );
    }

    /**
     * Listado de fuentes.
     */
    public function index(): string
    {
        $sources = $this->sources->all();

        $schedule = $this->summary->describe($sources);

        foreach ($sources as &$source) {
            $sourceId = (int) $source['id_source'];

            $source['map_url'] = $this->links->mapSource($sourceId);
            $source['test_url'] = $this->links->testSource($sourceId);
            $source['edit_url'] = $this->links->editSource($sourceId);
            $source['delete_url'] =
                $this->links->deleteSource($sourceId);

            $source['frequency_label'] = $this->shell->translate(
                $schedule[$sourceId]['frequency'] ?? 'Manual'
            );

            $source['next_run'] = $schedule[$sourceId]['next_run']
                ?? null;
        }

        unset($source);

        $this->shell->assign([
            'sources' => $sources,
            'source_form_url' => $this->links->sourceForm(),
            'history_url' => $this->links->history(),
            'import_url' => $this->links->import(),
            'monitor_url' => $this->monitorUrl,
        ]);

        return $this->shell->fetch('sources.tpl');
    }

    /**
     * Formulario de alta de una fuente.
     */
    public function form(): string
    {
        return $this->renderForm();
    }

    /**
     * Formulario de edición de una fuente existente.
     */
    public function edit(): string
    {
        $sourceId = (int) Tools::getValue('id_source');
        $source = $this->sources->find($sourceId);

        if ($source === null) {
            return $this->index();
        }

        return $this->renderForm(
            null,
            $source,
            $this->links->updateSource($sourceId)
        );
    }

    /**
     * Comprueba la conexión con la fuente y muestra una vista previa.
     */
    public function test(): string
    {
        $source = $this->sources->find(
            (int) Tools::getValue('id_source')
        );

        if ($source === null) {
            return $this->index();
        }

        try {
            $result = $this->sources->read($source);

            $this->shell->assign([
                'source' => $source,
                'total' => $result['total'],
                'headers' => $result['headers'],
                'rows' => array_slice(
                    $result['rows'],
                    0,
                    self::PREVIEW_ROWS
                ),
                'back_url' => $this->links->home(),
            ]);

            return $this->shell->fetch('source-preview.tpl');

        } catch (\Throwable $e) {
            $this->shell->addError(
                'Could not connect to the source: %error%',
                [
                    '%error%' => $this->shell->translate(
                        $e->getMessage()
                    ),
                ]
            );
        }

        return $this->index();
    }

    /**
     * Crea una fuente a partir del formulario.
     */
    public function save(): string
    {
        $data = $this->readRequestData();

        $error = $this->validator->validate($data);

        if ($error !== null) {
            return $this->renderForm(
                $error->getMessage(),
                $data,
                $this->links->saveSource()
            );
        }

        $created = $this->sources->create($data);

        if ($created <= 0) {
            $this->shell->addError(
                'The source could not be created.'
            );

            return $this->renderForm();
        }

        $this->shell->addConfirmation(
            'The source was created successfully.'
        );

        return $this->index();
    }

    /**
     * Actualiza una fuente existente.
     */
    public function update(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        if ($this->sources->find($sourceId) === null) {
            return $this->index();
        }

        $data = $this->readRequestData();

        $error = $this->validator->validate($data);

        if ($error !== null) {
            return $this->renderForm(
                $error->getMessage(),
                array_merge(['id_source' => $sourceId], $data),
                $this->links->updateSource($sourceId)
            );
        }

        $updated = $this->sources->update($sourceId, $data);

        if (!$updated) {
            return $this->renderForm(
                'The source could not be updated.',
                array_merge(['id_source' => $sourceId], $data),
                $this->links->updateSource($sourceId)
            );
        }

        $this->shell->addConfirmation(
            'The source was updated successfully.'
        );

        return $this->index();
    }

    /**
     * Elimina una fuente.
     */
    public function delete(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        if ($sourceId <= 0) {
            $this->shell->addError('The source is not valid.');

            return $this->index();
        }

        if ($this->sources->find($sourceId) === null) {
            $this->shell->addError(
                'The source could not be found.'
            );

            return $this->index();
        }

        if (!$this->sources->delete($sourceId)) {
            $this->shell->addError(
                'The source could not be deleted.'
            );

            return $this->index();
        }

        $this->shell->addConfirmation(
            'The source was deleted successfully.'
        );

        return $this->index();
    }

    private function renderForm(
        ?string $error = null,
        ?array $source = null,
        ?string $formAction = null
    ): string {
        $this->shell->assign([
            'source' => $source,
            'source_types' => $this->readers->types(),
            'frequency_options' => $this->frequencyOptions(),
            'saved_schedule' => DescribedFields::decode(
                $source['schedule'] ?? null
            ),
            'cancel_url' => $this->links->home(),
            'form_action' => $formAction
                ?? $this->links->saveSource(),
            'form_error' => $error !== null
                ? $this->shell->translate($error)
                : null,
        ]);

        return $this->shell->fetch('source-form.tpl');
    }

    /**
     * Frecuencias disponibles con sus etiquetas y sus campos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function frequencyOptions(): array
    {
        $options = [];

        foreach ($this->schedules->describeAll() as $schedule) {
            $options[] = [
                'name' => $schedule['name'],
                'label' => $this->shell->translate($schedule['label']),
                'fields' => DescribedFields::translate(
                    $schedule['fields'],
                    fn (string $text): string =>
                        $this->shell->translate($text)
                ),
            ];
        }

        return $options;
    }

    private function readRequestData(): array
    {
        $frequency = (string) Tools::getValue('frequency');

        return [
            'name' => trim((string) Tools::getValue('name')),
            'type' => (string) Tools::getValue('type'),
            'url' => trim((string) Tools::getValue('url')),
            'config' => trim((string) Tools::getValue('config')),
            'frequency' => $frequency,
            'schedule' => $this->readSchedule($frequency),
            'active' => (int) Tools::getValue('active'),
        ];
    }

    /**
     * Configuración del calendario de la frecuencia elegida.
     */
    private function readSchedule(string $frequency): string
    {
        $schedule = $this->schedules->find($frequency);

        if ($schedule === null) {
            return '';
        }

        $posted = Tools::getValue('schedule_config', []);

        if (!is_array($posted)) {
            return '';
        }

        $encoded = DescribedFields::encode(
            DescribedFields::collect(
                $schedule->describe()['fields'],
                $posted
            )
        );

        return (string) $encoded;
    }
}
