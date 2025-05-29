import { Usuario } from './usuario.model';
import { Especialista } from './especialista.model';

export interface Paciente {
    id_paciente: number;
    fecha_alta: string;
    fecha_baja: string | null;
    estado: 'activo' | 'inactivo';
    id_especialista: number;
    usuario: {
        id_usuario: number;
        nombre: string;
        apellidos: string;
        telefono: string;
        email: string;
        rol?: string;
        dni_usuario?: string;
        fecha_nacimiento?: string;
        fecha_creacion?: string;
        fecha_modificacion?: string;
        fecha_actualizacion?: string;
    };
    especialista?: {
        id_especialista: number;
        usuario: {
            id_usuario: number;
            nombre: string;
            apellidos: string;
        }
    };
}