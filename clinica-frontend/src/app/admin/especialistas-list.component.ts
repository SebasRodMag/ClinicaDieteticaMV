import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

interface Especialista {
    id: number;
    nombre: string;
    contacto: string;
    especialidad: string;
}

@Component({
    selector: 'app-especialistas-list',
    standalone: true,
    imports: [CommonModule],
    template: `
    <h3>Especialistas</h3>
        <table class="table table-striped">
        <thead>
            <tr>
            <th>Nombre</th>
            <th>Contacto</th>
            <th>Especialidad</th>
            <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr *ngFor="let e of especialistas">
            <td>{{ e.nombre }}</td>
            <td>{{ e.contacto }}</td>
            <td>{{ e.especialidad }}</td>
            <td>
                <button class="btn btn-sm btn-primary me-1" (click)="editar(e.id)">Editar</button>
                <button class="btn btn-sm btn-danger" (click)="eliminar(e.id)">Eliminar</button>
            </td>
            </tr>
        </tbody>
        </table>
    `,
})
export class EspecialistasListComponent {
    especialistas: Especialista[] = [
        { id: 1, nombre: 'Dr. Juan López', contacto: 'juan@mail.com', especialidad: 'Nutrición deportiva' },
        { id: 2, nombre: 'Dra. Marta Ruiz', contacto: 'marta@mail.com', especialidad: 'Dietoterapia' },
    ];

    editar(id: number) {
        console.log('Editar especialista', id);
    }

    eliminar(id: number) {
        console.log('Eliminar especialista', id);
    }
}
