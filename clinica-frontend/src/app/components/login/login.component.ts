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
    this.errorMessage = '';

    const { email, password } = this.loginForm.value;

    this.authService.login({ email, password }).subscribe({
      next: (response) => {
        // Guarda el usuario y token ya lo hace el AuthService con tap()

        this.loading = false;
        const role = response.user?.rol?.toLowerCase() || null;

        switch (role) {
          case 'administrador':
            this.router.navigate(['/administrador']);
            break;
          case 'especialista':
            this.router.navigate(['/especialista']);
            break;
          case 'paciente':
            this.router.navigate(['/paciente']);
            break;
          default:
            this.errorMessage = 'Rol no reconocido o no asignado';
            this.router.navigate(['/login']);
            break;
        }
      },
      error: (err) => {
        this.loading = false;
        this.errorMessage = err.error?.message || 'Credenciales incorrectas o error en el servidor';
      },
    });
  }
}
