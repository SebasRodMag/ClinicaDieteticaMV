export interface CitaActualizar {
    id_cita: number;
    id_paciente?: number;
    id_especialista?: number;
    fecha_hora_cita?: string; // 'YYYY-MM-DD HH:mm:ss'
    tipo_cita?: 'telemática' | 'presencial';
    estado?: 'pendiente' | 'realizada' | 'cancelada' | 'ausente' | 'reasignada' | 'finalizada';
    comentario?: string | null;
}
