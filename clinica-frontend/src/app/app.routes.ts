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
import { CitasListComponent } from './admin/citas-list.component';
import { LogsListComponent } from './admin/logs-list.component';
import { HistorialListComponent } from './Especialistas/historial-list.component';
import { EspecialistaCitasComponent } from './Especialistas/especialista-citas.component';
import { PacienteHistorialListComponent } from './Pacientes/paciente-historial-list.component';
import { PacientesCitasComponent } from './Pacientes/pacientes-citas.component';
import { PacienteDocumentosComponent } from './Pacientes/paciente-documentos.component';
import { EspecialistaDocumentosComponent } from './Especialistas/especialista-documentos.component';

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
            { path: 'citas', component: CitasListComponent },
            { path: 'configuracion', component: ConfiguracionComponent },
            { path: 'logs', component: LogsListComponent },
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
            { path: 'citas', component: PacientesCitasComponent },
            { path: 'historiales', component: PacienteHistorialListComponent },
            { path: 'documentos', component: PacienteDocumentosComponent },
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
            { path: 'citas', component: EspecialistaCitasComponent },
            { path: 'historiales', component: HistorialListComponent },
            { path: 'documentos', component: EspecialistaDocumentosComponent },
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
