export interface CitaPorEspecialista {
    id: number;
    fecha: string;
    hora: string;
    nombre_especialista: string;
    especialidad: string;
    estado: 'pendiente' | 'realizada' | 'cancelada';
    tipo_cita: string;
}