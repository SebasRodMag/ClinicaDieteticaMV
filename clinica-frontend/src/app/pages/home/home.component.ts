import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-home',
  imports: [CommonModule, RouterLink],
  templateUrl: './home.component.html',
  styleUrl: './home.component.css'
})
export class HomeComponent {
  beneficios = [
    {
      titulo: 'Energía y Vitalidad',
      descripcion: 'Una alimentación equilibrada es el combustible que tu cuerpo necesita...'
    },
    {
      titulo: 'Salud y Prevención',
      descripcion: 'Fortalece tu sistema inmunológico y reduce el riesgo de enfermedades...'
    },
    {
      titulo: 'Bienestar Integral',
      descripcion: 'La relación entre lo que comes y cómo te sientes es profunda...'
    },
    {
      titulo: 'Manejo de Peso Sostenible',
      descripcion: 'Olvídate de las dietas restrictivas. Te enseñamos a nutrir tu cuerpo...'
    }
  ];
}
