import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { UserService } from '../User-Service/user.service';

@Injectable({
    providedIn: 'root',
})
export class ConfiguracionService {
    private colorTemaSubject = new BehaviorSubject<string>('#28a745');
    colorTema$ = this.colorTemaSubject.asObservable();

    constructor(private userService: UserService) { }

    cargarConfiguracion(): void {
        this.userService.getConfiguracion().subscribe({
            next: (respuesta) => {
                const color = respuesta.configuraciones?.['color_tema'] || '#28a745';
                this.colorTemaSubject.next(color);
            },
            error: () => {
                console.warn('No se pudo cargar la configuración');
            }
        });
    }

    actualizarColorTema(nuevoColor: string): void {
        this.colorTemaSubject.next(nuevoColor);
    }
}
