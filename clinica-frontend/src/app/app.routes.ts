<<<<<<< HEAD
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
=======
// src/app/app.routes.ts
import { Routes } from '@angular/router';
import { RoleGuard } from './auth/role.guard';

export const routes: Routes = [
    { path: '', redirectTo: 'login', pathMatch: 'full' },
    {
        path: 'admin/dashboard',
        loadComponent: () =>
        import('./components/Admin/Dashboard/body/home/home.component').then((m) => m.HomeComponent),
        canActivate: [RoleGuard],
        data: { roles: ['Administrador'] },
    },
    {
        path: 'medico/dashboard',
        loadComponent: () =>
        import('./components/medico/medico.component').then((m) => m.MedicoComponent),
        canActivate: [RoleGuard],
        data: { roles: ['Medico'] },
    },
    {
        path: 'login',
        loadComponent: () =>
        import('./auth/login/login.component').then((m) => m.LoginComponent),
    },
    // Rutas para el cliente, Falta crear el controlador y el servicio
/*   {
        path: 'cliente',
        loadComponent: () => import('./components/clinete/cliente.component').then(m => m.ClienteComponent),
    }, */
    {
        path: 'citas',
        loadComponent: () =>
        import('./components/Admin/Dashboard/body/citas/citas.component').then((m) => m.CitasComponent),
    },
    {
        path: 'medicos',
        loadComponent: () => import('./components/Admin/Dashboard/body/medicos/medicos.component').then(m => m.MedicosComponent),
    },
    {
        path: 'pacientes',
        loadComponent: () =>
        import('./components/Admin/Dashboard/body/pacientes/pacientes.component').then((m) => m.PacientesComponent),
    },
    {
        path: 'usuarios',
        loadComponent: () =>
        import('./components/Admin/Dashboard/body/usuarios/usuarios.component').then((m) => m.UsuariosComponent),
    },
    {
        path: 'cards',
        loadComponent: () =>
        import('./components/Admin/Dashboard/body/cards/cards.component').then((m) => m.CardsComponent),
    },
    {
        path: 'clientes',
        loadComponent: () =>
        import('./components/Admin/Dashboard/body/clientes/clientes.component').then((m) => m.ClientesComponent),
    },
    {
        path: 'clientes/:id/pacientes', 
        loadComponent: () =>
        import('./components/Admin/Dashboard/body/pacientes/pacientes.component').then((m) => m.PacientesComponent),
    },
    {
        path: 'clientes/:id/contratos', 
        loadComponent: () =>
        import('./components/Admin/Dashboard/body/contratos/contratos.component').then((m) => m.ContratosComponent),
    },
    {
        path: '**',
        redirectTo: 'home',
    },

    // Rutas para el administrador, Falta crear el controlador y el servicio
    /* {
        path: 'admin',
        loadComponent: () => import('./components/Admin/Dashboard/body/').then(m => m.AdminComponent),
    }, */
>>>>>>> 421cfda064b38e409ce148c920dde9c6b4da21f5
];
