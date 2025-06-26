import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home.component';
import { LoginComponent } from './components/login/login.component';
import { RegisterComponent } from './components/register/register.component';

import { AdminDashboardComponent } from './admin/admin-dashboard.component';
import { HomeDashboardComponent } from './admin/home-dashboard.component';
import { PacientesListComponent } from './admin/pacientes-list.component';
import { EspecialistasListComponent } from './admin/especialistas-list.component';
import { UsuariosListComponent } from './admin/usuarios-list.component';
import { ConfiguracionComponent } from './admin/configuracion/configuracion.component';

import { PacienteDashboardComponent } from './Pacientes/paciente-dashboard.component';
import { EspecialistaDashboardComponent } from './Especialistas/especialista-dashboard.component';
import { UsuariosDashboardComponent } from './Usuarios/usuarios-dashboard.component';

import { AuthGuard } from './auth.guard';
import { CapaAuthComponent } from './components/capa-auth/capa-auth.component';

export const routes: Routes = [
    { path: '', component: HomeComponent },

    // Login y registro
    {
        path: '',
        component: CapaAuthComponent,
        children: [
            { path: 'login', component: LoginComponent },
            { path: 'register', component: RegisterComponent },
        ],
    },

    // ADMINISTRADOR
    {
        path: 'administrador',
        component: AdminDashboardComponent,
        canActivate: [AuthGuard],
        canActivateChild: [AuthGuard],
        data: { roles: ['administrador'] },
        children: [
            { path: '', component: HomeDashboardComponent },
            { path: 'pacientes', component: PacientesListComponent },
            { path: 'especialistas', component: EspecialistasListComponent },
            { path: 'usuarios', component: UsuariosListComponent },
            { path: 'admin/configuracion', component: ConfiguracionComponent },
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

    // Fallback
    { path: '**', redirectTo: '' },
];
