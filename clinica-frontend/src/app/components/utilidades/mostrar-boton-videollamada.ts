import { CitaGenerica } from '../../models/cita-generica.model';
import { construirFechaHoraLocal } from './sanitizar.utils';

export interface VentanaVideollamadaOpts {
    //pasamos por parametro el tiempo que necesitamos el botón visible
    minutosAntes?: number;
    minutosDespues?: number;
    requiereSala?: boolean;
}

/**
 * Determina si se debe mostrar el botón de videollamada según fecha, hora y tipo de cita.
 * Por defecto: desde 5 min antes hasta 30 min después del inicio.
 * Soporta 'telemática' y 'telematica' (sin tilde).
 */
export function mostrarBotonVideollamada(
    cita: CitaGenerica,
    opts: VentanaVideollamadaOpts = {}
): boolean {
    if (!cita) return false;

    const tipo = (cita.tipo_cita || '').toLowerCase();
    const esTelematica = tipo === 'telemática' || tipo === 'telematica';
    if (!esTelematica) return false;

    const { minutosAntes = 5, minutosDespues = 30, requiereSala = false } = opts;

    const fechaHoraCita = construirFechaHoraLocal(cita.fecha, cita.hora);
    if (isNaN(fechaHoraCita.getTime())) return false;

    const ahora = new Date();
    const inicioPermitido = new Date(fechaHoraCita.getTime() - minutosAntes * 60 * 1000);
    const finPermitido = new Date(fechaHoraCita.getTime() + minutosDespues * 60 * 1000);

    if (requiereSala) {
        const tieneSala = Boolean((cita as any).nombre_sala);
        if (!tieneSala) return false;
    }

    return ahora >= inicioPermitido && ahora <= finPermitido;
}