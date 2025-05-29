import { Component } from '@angular/core';
import { RouterOutlet, Routes, RouterModule } from '@angular/router';
import { LoginComponent } from './auth/login/login.component';
import { MedicoComponent } from './components/medico/medico.component';
import { HeaderComponent } from './components/Admin/Dashboard/header/header.component';
import { BodyComponent } from './components/Admin/Dashboard/body/body.component';

const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' }, // Redirigir a login por defecto
  { path: 'login', component: LoginComponent },
  { path: 'medico', component: MedicoComponent },
  // Otras rutas que necesites agregar
];

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, HeaderComponent, BodyComponent], // Aquí se configura RouterModule con rutas
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'Frontend';
}