import { Usuario } from './usuario.model';

export interface Especialista {
    id: number;
    user_id: number;
    especialidad: string;
    updated_at: string;
    created_at: string;
    deleted_at: string | null;
    usuario: Usuario;
} 