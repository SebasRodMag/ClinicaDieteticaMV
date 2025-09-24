import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { UserService } from '../User-Service/user.service';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({
    providedIn: 'root',
})
export class ConfiguracionService {

    private aplicarColorTema(color: string): void {
        document.documentElement.style.setProperty('--color-tema', color);
    }

    //Exponemos el color como observable. Se establece un color como predefinido, por si el backend falla.
    private colorTemaSubject = new BehaviorSubject<string>('#28a745');
    colorTema$ = this.colorTemaSubject.asObservable();

    constructor(private userService: UserService, private http: HttpClient) { }

    cargarConfiguracion(): void {
        this.userService.getConfiguracion().subscribe({
            next: (respuesta) => {
                const color = respuesta.configuraciones?.['color_tema'] || '#28a745';
                this.colorTemaSubject.next(color);
                this.aplicarColorTema(color);
            },
            error: () => {
                console.warn('No se pudo cargar la configuración');
            }
        });
    }

    actualizarColorTema(nuevoColor: string): void {
        this.colorTemaSubject.next(nuevoColor);
        this.aplicarColorTema(nuevoColor);
    }

    cargarColorTemaPublico(): void {
        this.http
            .get<{ color_tema?: string }>(`${environment.apiBase}/color-tema`, { responseType: 'json' as const })
            .subscribe({
                next: (respuesta) => {
                    const color = respuesta?.color_tema ?? '#28a745';
                    this.colorTemaSubject.next(color);
                    this.aplicarColorTema(color);
                },
                error: () => {
                    console.warn('No se pudo cargar el color del tema. Usando valor por defecto.');
                    this.actualizarColorTema('#28a745');
                },
            });
    }

    getResumenDashboard(): Observable<any> {
        return this.http.get(`${environment.apiBase}/admin/resumen-dashboard`);
    }
}
