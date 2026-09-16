<?php

namespace CPBConnect\Presentation\Admin;

/**
 * Acciones de administración que aporta una edición adicional.
 *
 * El router principal sólo conoce las acciones de la edición
 * gratuita; cuando recibe una que no reconoce consulta esta interfaz
 * antes de dar por hecho que la acción no existe.
 */
interface AdminActionsInterface
{
    /**
     * Devuelve el HTML de la acción o null si no la reconoce.
     */
    public function handle(string $action): ?string;
}
