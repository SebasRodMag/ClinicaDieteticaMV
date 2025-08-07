import { CitaPorPaciente } from './citasPorPaciente.model';
import { CitaPorEspecialista } from './citasPorEspecialista.model';

export type CitaGenerica = CitaPorPaciente | CitaPorEspecialista;