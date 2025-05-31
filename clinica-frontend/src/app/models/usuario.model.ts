export interface Usuario {
    id: number;
    nombre: string;
    apellidos: string;
    dni_usuario: string;
    email: string;
    direccion?: string;
    fecha_nacimiento?: string;
    telefono?: string;
    password?: string;
    password_confirmation?: string;
    email_verified_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    deleted_at?: string | null;
}