import { Routes } from '@angular/router';
import { LoginComponent } from './components/login/login.component';
import { HomeComponent } from './pages/home/home.component';
import { AdminDashboardComponent } from './admin/admin-dashboard.component';
import { PacientesListComponent } from './admin/pacientes-list.component';
import { EspecialistasListComponent } from './admin/especialistas-list.component';
import { UsuariosListComponent } from './admin/usuarios-list.component';
import { AuthGuard } from './auth.guard';

export const routes: Routes = [
    { path: '', component: HomeComponent },
    { path: 'login', component: LoginComponent },
    {
        path: 'admin',
        component: AdminDashboardComponent,
        canActivate: [AuthGuard],
        canActivateChild: [AuthGuard],
        data: { roles: ['administrador'] },
        children: [
            { path: 'pacientes', component: PacientesListComponent },
            { path: 'especialistas', component: EspecialistasListComponent },
            { path: 'usuarios', component: UsuariosListComponent },
            { path: '', redirectTo: 'pacientes', pathMatch: 'full' },
        ],
    },
    { path: '**', redirectTo: '' },
];
