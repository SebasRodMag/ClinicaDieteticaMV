/**modal-create.component.ts aporta la logica al modal para crear una cita */
import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Output, AfterViewInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgSelectComponent, NgSelectModule } from '@ng-select/ng-select';

declare var $: any;
/*
* Se declara el componente ModalCreateComponent que representa un modal para crear citas.
* Este componente utiliza NgSelect para seleccionar pacientes. 
* El modal se puede abrir y cerrar, y emite un evento cuando se cierra.
*/
@Component({
    selector: 'app-modal-create',
    imports: [CommonModule, FormsModule, NgSelectModule],
    templateUrl: './modal-create.component.html',
    styleUrl: '../../../../../../../styles.css',
})

/*Clase ModalCreateComponent que implementa la lógica del modal para crear citas */
export class ModalCreateComponent {
    isVisible = false;

    @Output() closed = new EventEmitter<void>();

    open() {
        this.isVisible = true;
    }

    close() {
        this.isVisible = false;
        this.closed.emit();
    }


    selectedPacientes: string[] = [];
    pacientes: any[] = [];
}
