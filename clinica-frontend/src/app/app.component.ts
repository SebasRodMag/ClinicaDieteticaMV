import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { ConfiguracionService } from './service/Config-Service/configuracion.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  template: `<router-outlet></router-outlet>`
})
export class AppComponent {
  constructor(private toastr: ToastrService, private configService: ConfiguracionService) {

    // Cargar la configuración del color_tema al iniciar la aplicación
      this.configService.cargarColorTemaPublico();
      this.configService.colorTema$.subscribe(color => {
      document.documentElement.style.setProperty('--color-tema', color);
    });
  }
  

  mostrarMensajeExito() {
    this.toastr.success('Registro completado', 'Éxito');
  }

  
}
