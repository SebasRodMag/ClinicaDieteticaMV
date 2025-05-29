export interface Log {
    id_log: number;
    id_usuario: number;
    accion: string;
    fecha_hora: string;
    detalles?: string;
}