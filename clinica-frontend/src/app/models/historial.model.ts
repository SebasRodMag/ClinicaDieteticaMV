import { Paciente } from './paciente.model';
import { Especialista } from './especialista.model';
import { Cita } from './cita.model';

export interface Historial {
    id: number;
    id_paciente: number;
    id_especialista: number;
    id_cita: number | null;
    fecha: string | null; // 'YYYY-MM-DD'
    comentarios_paciente: string | null;
    observaciones_especialista: string | null;
    recomendaciones: string | null;
    dieta: string | null;
    lista_compra: string | null;
    created_at: string; // 'YYYY-MM-DD HH:mm:ss'
    updated_at: string; // 'YYYY-MM-DD HH:mm:ss'
    deleted_at: string | null;

    paciente?: Paciente | null;
    especialista?: Especialista | null;
    cita?: Cita | null;
}