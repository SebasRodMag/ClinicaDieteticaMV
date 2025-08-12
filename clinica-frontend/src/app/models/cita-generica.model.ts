import { CitaPorPaciente } from './citasPorPaciente.model';
import { CitaPorEspecialista } from './citasPorEspecialista.model';

export type CitaGenerica = CitaPorPaciente | CitaPorEspecialista;

export type CitaGenericaExtendida = CitaGenerica & Partial<{
    id_paciente: number;
    paciente_id: number;
    paciente: { id: number };
}>;