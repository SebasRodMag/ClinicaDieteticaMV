import { Component } from '@angular/core';
import { ModalCreateComponent } from './modal-create/modal-create.component';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { ClienteService } from '../../../../../services/Cliente-Service/cliente.service';

/**
 * ClientesComponent
 * Componente que muestra una lista de clientes y permite crear nuevos clientes.
 * Este componente utiliza el servicio ClienteService para obtener los datos de los clientes.
 * También incluye un modal para crear nuevos clientes.
 */
@Component({
    selector: 'app-clientes',
    standalone: true,
    imports: [ModalCreateComponent, RouterLink, CommonModule],
    templateUrl: './clientes.component.html',
    styleUrls: ['./clientes.component.css'],
})

/**
 * Clase ClientesComponent
 * Representa el componente de clientes en la aplicación.
 * Contiene la lógica para obtener y mostrar una lista de clientes.
 * Incluye un modal para crear nuevos clientes.
 * 
 */
export class ClientesComponent {
    clientes: any[] = [];

    constructor(private clienteService: ClienteService) {}

    ngOnInit() {
        this.clienteService.getClientes().subscribe(
            (data) => {
                console.log('Clientes: ', data);
                this.clientes = data;
            },
            (error) => console.error('Error al obtener los clientes', error)
        );
    }
}
