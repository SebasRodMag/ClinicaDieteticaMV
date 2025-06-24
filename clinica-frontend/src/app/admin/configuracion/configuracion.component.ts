import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { UserService } from '../../service/User-Service/user.service';
import { ConfiguracionService } from '../../service/Config-Service/configuracion.service';

@Component({
  selector: 'app-configuracion',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatSnackBarModule
  ],
  templateUrl: './configuracion.component.html',
  styleUrls: ['./configuracion.component.css']
})
export class ConfiguracionComponent implements OnInit {
  configuraciones: { [clave: string]: any } = {};
  descripciones: { [clave: string]: string } = {
    duracion_cita: 'Duración de cada cita en minutos',
    precio_cita: 'Precio base por cita',
    dias_no_laborables: 'Fechas en las que no se puede agendar',
    horario_laboral: 'Horarios de apertura y cierre de la clínica',
    notificaciones_email: 'Habilita o deshabilita notificaciones por correo',
    color_tema: 'Color base del sistema',
    Crear_cita_paciente: 'Permitir que los pacientes creen citas',
    Especialidades: 'Listado de especialidades disponibles'
  };

  loading = true;
  modalAbierto = false;
  claveSeleccionada: string | null = null;

  //Estructura para la configuración seleccionada
  configuracionSeleccionada: {
    clave: string;
    valor: any;
    valorJson?: string;
    jsonValido?: boolean;
    descripcion: string;
  } | null = null;

  public Object = Object;

  constructor(
    private userService: UserService,
    private snackBar: MatSnackBar,
    private ConfiguracionService: ConfiguracionService
  ) { }

  ngOnInit(): void {
    this.obtenerConfiguracion();
  }

  obtenerConfiguracion(): void {
    this.loading = true;
    this.userService.getConfiguracion().subscribe({
      next: (respuesta) => {
        this.configuraciones = respuesta.configuraciones;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.mostrarMensaje('Error al obtener configuración', 'error');
      }
    });
  }

  abrirModal(clave: string): void {
    this.claveSeleccionada = clave;
    const valor = structuredClone(this.configuraciones[clave]);

    this.configuracionSeleccionada = {
      clave,
      valor,
      valorJson: typeof valor === 'object' ? JSON.stringify(valor, null, 2) : '',
      jsonValido: true,
      descripcion: this.descripciones[clave] || 'Sin descripción disponible',
    };
    this.modalAbierto = true;
  }

  cerrarModal(): void {
    this.modalAbierto = false;
    this.claveSeleccionada = null;
    this.configuracionSeleccionada = null;
  }

  validarJson(config: any): void {
    try {
      JSON.parse(config.valorJson);
      config.jsonValido = true;
    } catch {
      config.jsonValido = false;
    }
  }

  guardarConfiguracion(): void {
    if (!this.configuracionSeleccionada) return;

    let valorParaEnviar: string;
    if (typeof this.configuracionSeleccionada.valor === 'object') {
      if (this.configuracionSeleccionada.jsonValido && this.configuracionSeleccionada.valorJson) {
        valorParaEnviar = this.configuracionSeleccionada.valorJson;
      } else {
        valorParaEnviar = JSON.stringify(this.configuracionSeleccionada.valor);
      }
    } else {
      valorParaEnviar = String(this.configuracionSeleccionada.valor);
    }

    this.userService.updateConfiguracionPorClave(
      this.configuracionSeleccionada.clave,
      { valor: valorParaEnviar }
    ).subscribe({
      next: () => {
        this.mostrarMensaje('Configuración actualizada correctamente', 'success');

        if (this.configuracionSeleccionada?.clave === 'color_tema') {
          this.ConfiguracionService.actualizarColorTema(valorParaEnviar);
        }

        this.obtenerConfiguracion();
        this.cerrarModal();
      },
      error: () => {
        this.mostrarMensaje('Error al actualizar la configuración', 'error');
      }
    });
  }

  mostrarMensaje(mensaje: string, tipo: 'success' | 'error'): void {
    this.snackBar.open(mensaje, 'Cerrar', {
      duration: 3000,
      panelClass: tipo === 'success' ? ['snackbar-success'] : ['snackbar-error']
    });
  }

  claveExpandida: string | null = null;

  toggleExpand(clave: string): void {
    this.claveExpandida = this.claveExpandida === clave ? null : clave;
  }

  tipoTextoSimple(valor: any): boolean {
    return typeof valor !== 'object' && typeof valor !== 'boolean' && typeof valor !== 'number';
  }


}
