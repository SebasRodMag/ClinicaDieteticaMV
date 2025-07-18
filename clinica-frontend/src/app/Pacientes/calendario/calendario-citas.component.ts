import { Component, Input, Output, OnInit, OnChanges, SimpleChanges, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CalendarOptions } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import { FullCalendarModule } from '@fullcalendar/angular';
import { CitaPorEspecialista } from '../../models/citasPorEspecialista.model';
import { ModalInfoCitaComponent } from './modal/modal-info-cita.component';
import { ConfiguracionService } from '../../service/Config-Service/configuracion.service';

@Component({
    selector: 'app-calendario-citas',
    standalone: true,
    imports: [CommonModule, FullCalendarModule, ModalInfoCitaComponent],
    templateUrl: './calendario-citas.component.html',
    styleUrls: ['./calendario-citas.component.css'],
})
export class CalendarioCitasComponent implements OnInit, OnChanges {
    @Input() citas: CitaPorEspecialista[] = [];
    @Output() citaCancelada = new EventEmitter<number>();

    citaSeleccionada: CitaPorEspecialista | null = null;
    colorSistema: string = '#28a745';

    calendarOptions: CalendarOptions = {
        plugins: [dayGridPlugin],
        initialView: 'dayGridMonth',
        events: [], // Se cargan dinámicamente en ngOnChanges
        eventClick: this.onCitaClick.bind(this),
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth',
        },
        height: 'auto',
    };

    constructor(private configService: ConfiguracionService) {}

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

        this.calendarOptions.events = this.citas.map((cita) => ({
            id: String(cita.id),
            title: `${cita.nombre_especialista} (${cita.especialidad})`,
            date: cita.fecha.includes('T') ? cita.fecha.split('T')[0] : cita.fecha,
            extendedProps: cita,
        }));

        console.log('Citas cargadas en el calendario:', this.calendarOptions.events);
    }

    onCitaClick(event: any): void {
        const id = +event.event.id;
        this.citaSeleccionada = this.citas.find(c => c.id === id) || null;
    }

    cerrarModal(): void {
        this.citaSeleccionada = null;
    }

    cancelarCita(idCita: number): void {
        this.citaCancelada.emit(idCita);
    }
}
