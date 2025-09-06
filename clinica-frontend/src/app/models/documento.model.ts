export interface Documento {
    id: number;
    historial_id: number | null;
    user_id: number;
    nombre: string;
    archivo: string;
    tipo: string;
    visible_para_especialista: boolean | number;
    tamano: number;
    descripcion?: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    historial?: any;
}