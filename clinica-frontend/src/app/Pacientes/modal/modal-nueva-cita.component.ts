import { Component, EventEmitter, Output, Input, OnInit, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { UserService } from '../../service/User-Service/user.service';
import { Paciente } from '../../models/paciente.model';
import { Especialista } from '../../models/especialista.model';



@Component({
    selector: 'app-modal-nueva-cita',
    standalone: true,
    templateUrl: './modal-nueva-cita.component.html',
    imports: [CommonModule, FormsModule, MatSnackBarModule]
})
export class ModalNuevaCitaComponent implements OnInit {
    @Input() modalVisible!: boolean;
    @Input() idPaciente!: number;
    @Input() idEspecialista!: number;
    
    @Output() creada = new EventEmitter<void>();
    @Output() cerrado = new EventEmitter<void>();

    pacientes: Paciente[] = [];
    especialistas: Especialista[] = [];

    pacienteSeleccionado: number | null = null;
    especialistaSeleccionado: number | null = null;
    fechaHora: string = '';
    tipoCita: 'presencial' | 'teleática' = 'presencial';
    comentarios: string = '';
    cargando = false;

    constructor( private UserService: UserService, private snackBar: MatSnackBar) {}

    ngOnInit(): void {
        // Cargar pacientes y especialistas (implementa estas llamadas en tu servicio)
        this.UserService.getPacientes().subscribe({
            next: (data) => this.pacientes = data,
            error: () => this.snackBar.open('Error al cargar pacientes', 'Cerrar', { duration: 3000 })
        });

        this.UserService.getEspecialistas().subscribe({
            next: (data) => this.especialistas = data,
            error: () => this.snackBar.open('Error al cargar especialistas', 'Cerrar', { duration: 3000 })
        });
    }

    confirmar(): void {
        if (!this.pacienteSeleccionado || !this.especialistaSeleccionado || !this.fechaHora || !this.tipoCita) {
            this.snackBar.open('Complete todos los campos obligatorios', 'Cerrar', { duration: 3000 });
            return;
        }

        this.cargando = true;

        this.UserService.crearCita({
            paciente_id: this.pacienteSeleccionado,
            especialista_id: this.especialistaSeleccionado,
            fecha_hora: this.fechaHora,
            tipo: this.tipoCita,
            comentarios: this.comentarios
        }).subscribe({
            next: () => {
                this.snackBar.open('Cita creada correctamente', 'Cerrar', { duration: 3000 });
                this.creada.emit();
                this.cerrar();
            },
            error: () => {
                this.snackBar.open('Error al crear la cita', 'Cerrar', { duration: 3000 });
            },
            complete: () => this.cargando = false
        });
    }

    cerrar(): void {
        this.cerrado.emit();
    }

    @HostListener('document:keydown.escape', ['$event'])
    handleEscapeKey(event: KeyboardEvent) {
        if (this.modalVisible) this.cerrar();
    }
}
