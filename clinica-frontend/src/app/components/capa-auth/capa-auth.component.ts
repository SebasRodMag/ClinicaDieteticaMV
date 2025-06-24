import { Component, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';
import { UserService } from '../../service/User-Service/user.service';
import { ConfiguracionService } from '../../service/Config-Service/configuracion.service';

@Component({
  selector: 'app-capa-auth',
  standalone: true,
  imports: [CommonModule, RouterOutlet],
  templateUrl: './capa-auth.component.html',
  styleUrl: './capa-auth.component.css'
})
export class CapaAuthComponent implements OnInit {
  constructor(private configuracionService: ConfiguracionService) { }
  colorTema = '#28a745'; //Valor por defecto en caso de error

  ngOnInit(): void {
    this.configuracionService.cargarColorTemaPublico();
  }
}