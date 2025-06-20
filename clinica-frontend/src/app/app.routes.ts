import { Routes } from '@angular/router';
import { LoginComponent } from './components/login/login.component';
import { HomeComponent } from './pages/home/home.component';
import { RegisterComponent } from './components/register/register.component';

// Componentes por rol
import { AdminDashboardComponent } from './admin/admin-dashboard.component';
import { PacientesListComponent } from './admin/pacientes-list.component';
import { EspecialistasListComponent } from './admin/especialistas-list.component';
import { UsuariosListComponent } from './admin/usuarios-list.component';

import { PacienteDashboardComponent } from './Pacientes/paciente-dashboard.component';
import { EspecialistaDashboardComponent } from './Especialistas/especialista-dashboard.component';
import { UsuariosDashboardComponent } from './Usuarios/usuarios-dashboard.component';
import { ConfiguracionComponent } from './admin/configuracion/configuracion.component';

import { AuthGuard } from './auth.guard';

export const routes: Routes = [
    { path: '', component: HomeComponent },
    { path: 'login', component: LoginComponent },
    { path: 'register', component: RegisterComponent },

    // ADMINISTRADOR
    {
        path: 'administrador',
        component: AdminDashboardComponent,
        canActivate: [AuthGuard],
        canActivateChild: [AuthGuard],
        data: { roles: ['administrador'] },
        children: [
            { path: 'pacientes', component: PacientesListComponent },
            { path: 'especialistas', component: EspecialistasListComponent },
            { path: 'usuarios', component: UsuariosListComponent },
            { path: 'admin/configuracion', component: ConfiguracionComponent },
            { path: '', redirectTo: 'pacientes', pathMatch: 'full' },
        ],
    },

    // PACIENTE
    {
        path: 'paciente',
        component: PacienteDashboardComponent,
        canActivate: [AuthGuard],
        canActivateChild: [AuthGuard],
        data: { roles: ['paciente'] },
        children: [
            { path: 'citas', component: PacienteDashboardComponent },
            { path: '', redirectTo: 'citas', pathMatch: 'full' },
        ],
    },

    // ESPECIALISTA
    {
        path: 'especialista',
        component: EspecialistaDashboardComponent,
        canActivate: [AuthGuard],
        canActivateChild: [AuthGuard],
        data: { roles: ['especialista'] },
        children: [
            { path: 'citas', component: EspecialistaDashboardComponent },
            { path: '', redirectTo: 'citas', pathMatch: 'full' },
        ],
    },

    // USUARIO
    {
        path: 'usuario',
        component: UsuariosDashboardComponent,
        canActivate: [AuthGuard],
        canActivateChild: [AuthGuard],
        data: { roles: ['usuario'] },
        children: [
            { path: 'citas', component: UsuariosDashboardComponent },
        ],
    },

    // Resto
    { path: '**', redirectTo: '' }
];