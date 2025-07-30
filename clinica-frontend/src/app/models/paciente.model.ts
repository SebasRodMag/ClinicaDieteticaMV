import { Especialista } from './especialista.model';
import { Cita } from './cita.model';
import { Usuario } from './usuario.model';

export interface Paciente {
    id: number;
    user_id: number;
    numero_historial: string;
    fecha_alta: string;
    fecha_baja: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    ultima_cita: Cita | null;
    especialista: Especialista | null;
    user: Usuario | null;
}

type PacienteExtendido = Paciente & {
    nombre_paciente: string;
    nombre_especialista: string;
};