import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { ModalCreateComponent } from './modal-create/modal-create.component';

@Component({
  selector: 'app-citas',
  imports: [
    CommonModule, ModalCreateComponent
  ],
  templateUrl: './citas.component.html',
  styleUrl: './citas.component.css'
})
export class CitasComponent {

}
