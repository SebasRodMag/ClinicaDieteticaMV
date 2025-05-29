import { CommonModule } from '@angular/common';
import { PacienteEditComponent } from './modal/paciente-edit.component';
import { Paciente } from '../models/paciente.model';
import { Component } from '@angular/core';
import { UserService } from '../service/User-Service/user.service';

@Component({
    selector: 'app-pacientes-list',
    standalone: true,
    imports: [CommonModule, PacienteEditComponent],
    templateUrl: './pacientes-list.component.html',
})

export class PacientesListComponent {
    pacientes: Paciente[] = [];

    pacienteEditando: Paciente | null = null;

    constructor(private userService: UserService) {}

    

ngOnInit() {
    this.userService.getFullPacientes().subscribe((data: any[]) => {
        this.pacientes = data.map((item: any) => ({
            id_paciente: item.id_paciente,
            fecha_alta: item.fecha_alta,
            fecha_baja: item.fecha_baja,
            estado: item.estado,
            id_especialista: item.id_especialista,
            usuario: {
                id_usuario: item.id_usuario,
                nombre: item.nombre,
                apellidos: item.apellidos,
                telefono: item.telefono,
                email: item.email,
            },
            especialista: item.especialista ? {
                id_especialista: item.especialista.id_especialista,
                usuario: {
                    id_usuario: item.especialista.usuario.id_usuario,
                    nombre: item.especialista.usuario.nombre,
                    apellidos: item.especialista.usuario.apellidos
                }
            } : undefined
        }));
    });
}
    abrirModal(paciente: Paciente) {
        this.pacienteEditando = { ...paciente };
    }

    actualizar(pacienteActualizado: Paciente) {
        const index = this.pacientes.findIndex(p => p.id_paciente === pacienteActualizado.id_paciente);
        if (index > -1) {
            this.pacientes[index] = { ...pacienteActualizado };
        }
        this.pacienteEditando = null;
    }

    eliminar(id: number) {
        this.pacientes = this.pacientes.filter(p => p.id_paciente !== id);
    }



}
