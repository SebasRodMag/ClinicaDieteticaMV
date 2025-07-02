export interface CitaListado {
    id_cita: number;
    id_paciente: number;
    id_especialista: number;
    fecha: string;          // 'YYYY-MM-DD'
    hora: string;           // 'HH:mm'
    tipo_cita: 'telemática' | 'presencial';
    estado: 'pendiente' | 'realizada' | 'cancelada';
    nombre_paciente: string;
    nombre_especialista: string;
    especialidad: string;   // Especialidad del especialista
    comentario: string;
}