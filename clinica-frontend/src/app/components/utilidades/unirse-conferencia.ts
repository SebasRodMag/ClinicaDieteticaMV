import { MatSnackBar } from '@angular/material/snack-bar';
import { Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';

/**
 * Método utilitario para unirse a una videollamada Jitsi
 */
export function unirseConferencia(
    idCita: number,
    http: HttpClient,
    snackBar: MatSnackBar,
    apiUrl: string
): void {
    http.get<{ nombre_sala: string }>(`${apiUrl}/citas/${idCita}/sala-segura`).subscribe({
        next: (response) => {
            const url = `https://meet.jit.si/${response.nombre_sala}`;
            window.open(url, '_blank');
        },
        error: (error) => {
            if (error.status === 403) {
                snackBar.open('No tienes permiso para unirte a esta videollamada', 'Cerrar', { duration: 3000 });
            } else if (error.status === 400) {
                snackBar.open('La cita no es telemática o no tiene sala activa', 'Cerrar', { duration: 3000 });
            } else {
                snackBar.open('Error al intentar acceder a la videollamada', 'Cerrar', { duration: 3000 });
            }
        }
    });
}
