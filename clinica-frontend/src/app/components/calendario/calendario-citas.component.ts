import { Component, Input, Output, OnInit, OnChanges, SimpleChanges, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CalendarOptions } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import { FullCalendarModule } from '@fullcalendar/angular';
import { ConfiguracionService } from '../../service/Config-Service/configuracion.service';
import { CitaGenerica } from '../../models/cita-generica.model';
import { convertirFechaAISO } from '../utilidades/sanitizar.utils';

@Component({
    selector: 'app-calendario-citas',
    standalone: true,
    imports: [CommonModule, FullCalendarModule],
    templateUrl: './calendario-citas.component.html',
    styleUrls: ['./calendario-citas.component.css'],
})
export class CalendarioCitasComponent implements OnInit, OnChanges {
    @Input() citas: CitaGenerica[] = [];
    @Output() citaClick = new EventEmitter<any>();
    @Output() citaCancelada = new EventEmitter<number>();
    @Output() recargarCitas = new EventEmitter<void>();

    

    colorSistema: string = '#b7bbc2ff';  //<-----Color por defecto #b7bbc2ff
    cargandoActualizarEstado: boolean = false;

    calendarOptions: CalendarOptions = {
        plugins: [dayGridPlugin],
        initialView: 'dayGridMonth',
        events: [],
        eventClick: (event) => this.citaClick.emit(this.obtenerCitaPorId(+event.event.id)),
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false,
            hour12: false,
        },
        firstDay: 1,
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '',
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día',
            list: 'Agenda',
        },
        height: 'auto',
    };

    constructor(private configService: ConfiguracionService) { }

    ngOnInit(): void {
        this.configService.colorTema$.subscribe(color => {
            this.colorSistema = color;
        });
        this.configService.cargarColorTemaPublico();
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['citas']) {
            this.actualizarEventos();
        }
    }

    private actualizarEventos(): void {
        if (!this.citas || this.citas.length === 0) {
            console.warn('No hay citas para mostrar en el calendario.');
            this.calendarOptions.events = [];
            return;
        }

        const eventos = this.citas.map((cita) => {
            const fechaISO = convertirFechaAISO(cita.fecha);
            const hora = cita.hora?.slice(0, 5) || '12:00';

            return {
                id: String(cita.id),
                title: this.generarTituloCita(cita),
                start: `${fechaISO}T${hora}`,
                allDay: false,
                extendedProps: cita,
            };
        });

        this.calendarOptions.events = eventos;
    }

    private generarTituloCita(cita: CitaGenerica): string {
        const nombre = (cita as any).nombre_especialista || (cita as any).nombre_paciente || 'Cita';
        const datoExtra = (cita as any).especialidad || (cita as any).dni_paciente || '';
        return `${nombre} (${datoExtra})`;
    }

    private obtenerCitaPorId(id: number): CitaGenerica | null {
        return this.citas.find(c => c.id === id) || null;
    }
}
