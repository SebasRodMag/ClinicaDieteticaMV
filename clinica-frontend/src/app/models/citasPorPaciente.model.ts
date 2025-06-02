export interface CitaPorPaciente {
    id: number;
    fecha: string;
    hora: string;
    especialidad: string;
    nombre_especialista: string;
    estado: 'pendiente' | 'realizada' | 'cancelada';
}