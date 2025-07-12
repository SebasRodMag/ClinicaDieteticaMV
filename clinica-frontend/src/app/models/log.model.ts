
import { Usuario} from './usuario.model';
export interface Log {
    id: number;
    user_id: number;
    accion: string;
    tabla_afectada: string;
    registro_id: string;
    created_at: string;
    user?: Usuario | null; // para acceder a nombre, apellidos, email en tabla
}