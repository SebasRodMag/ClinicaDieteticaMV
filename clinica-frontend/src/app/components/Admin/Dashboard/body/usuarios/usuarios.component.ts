import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ModalCreateComponent } from './modal-create/modal-create.component';

@Component({
    selector: 'app-usuarios',
    imports: [
        CommonModule, ModalCreateComponent
    ],
    templateUrl: './usuarios.component.html',
    styleUrl: './usuarios.component.css'
})
export class UsuariosComponent {

}