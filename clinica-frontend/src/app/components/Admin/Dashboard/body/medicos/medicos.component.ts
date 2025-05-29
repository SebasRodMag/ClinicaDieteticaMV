import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ModalCreateComponent } from './modal-create/modal-create.component';
import { MedicoService } from '../../../../../services/Medico-Service/medico.service';

@Component({
    selector: 'app-medicos',
    imports: [CommonModule, ModalCreateComponent],
    templateUrl: './medicos.component.html',
    styleUrls: ['./medicos.component.css'],
})
export class MedicosComponent {
    medicos: any = [];

    constructor(private medicoService: MedicoService) {}

    ngOnInit(): void {
        this.medicoService.getMedicos().subscribe(
            (data) => {
                console.log('Medicos: ', data);
                this.medicos = data;
            },
            (error) => console.error('Error al obtener los medicos', error)
        );
    }
}
