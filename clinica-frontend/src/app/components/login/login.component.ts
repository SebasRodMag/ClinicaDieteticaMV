import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../service/Auth-Service/Auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './login.component.html',
})
export class LoginComponent {
  loginForm: FormGroup;
  errorMessage: string = '';
  loading = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
  ) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
    });
  }

  onSubmit(): void {
    if (this.loginForm.invalid) return;

    this.loading = true;

    const { email, password } = this.loginForm.value;

    this.authService.login({ email, password }).subscribe({
      next: () => {
        const role = this.authService.getUserRole();

        this.loading = false;
        switch (role?.toLowerCase()) {
          case 'administrador':
            console.log('Usuario autenticado como administrador');
            this.router.navigate(['/admin']);
            break;
          case 'especialista':
            console.log('Usuario autenticado como especialista');
            this.router.navigate(['/especialista']);
            break;
          case 'paciente':
            console.log('Usuario autenticado como paciente');
            this.router.navigate(['/paciente']);
            break;
          default:
            console.log('Usuario no autenticado. recibido:'+role);
            console.log('User stored in localStorage:', localStorage.getItem('user'));
            this.errorMessage = 'Rol no reconocido o no asignado';
            this.router.navigate(['/login']);
        }
      },
      error: (err) => {
        this.loading = false;
        this.errorMessage = err.error?.message || 'Credenciales incorrectas o error en el servidor';
      },
    });
  }
}
