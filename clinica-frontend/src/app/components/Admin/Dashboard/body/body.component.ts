import { Component } from '@angular/core';
import { NavComponent } from '../header/nav/nav.component';
import { ClientesComponent } from './clientes/clientes.component';
import { UsuariosComponent } from './usuarios/usuarios.component';
import { MedicosComponent } from './medicos/medicos.component';
import { CitasComponent } from './citas/citas.component';
import { RouterModule, RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-body',
  imports: [
    RouterOutlet, 
  ],
  templateUrl: './body.component.html',
  styleUrl: './body.component.css'
})
export class BodyComponent {

}