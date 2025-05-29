export type EstadoConsulta = 'pendiente' | 'realizada' | 'cancelada';
export type TipoConsulta = 'presencial' | 'telemática';

export interface Consulta {
    id_consulta: number;
    id_especialista: number;
    id_paciente: number;
    tipo_consulta: TipoConsulta;
    fecha_hora_consulta: string;
    estado: EstadoConsulta;
    comentario: string;
}