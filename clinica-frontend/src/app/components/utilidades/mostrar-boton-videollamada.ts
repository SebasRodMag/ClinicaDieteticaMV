import { CitaPorEspecialista } from '../../models/citasPorEspecialista.model';
/**
 * Determina si se debe mostrar el botón de videollamada según fecha, hora y tipo de cita.
 * Rango: desde 15 minutos antes hasta 30 minutos después del horario programado.
 */
export function mostrarBotonVideollamada(cita: CitaPorEspecialista): boolean {
    const esTelematica = cita.tipo_cita === 'telemática';
    const tieneSala = !!cita.nombre_sala;

    const ahora = new Date();
    // Convertir fecha y hora a Date
    const fechaHoraCita = new Date(`${cita.fecha}T${cita.hora}`);

    //Se establece un rango de 15 minutos antes y 30 minutos después de la cita para poder realizar la videollamada
    const inicioPermitido = new Date(fechaHoraCita.getTime() - 15 * 60 * 1000);
    const finPermitido = new Date(fechaHoraCita.getTime() + 30 * 60 * 1000);

    const dentroDelRango = ahora >= inicioPermitido && ahora <= finPermitido;

    return esTelematica && tieneSala && dentroDelRango;
}
