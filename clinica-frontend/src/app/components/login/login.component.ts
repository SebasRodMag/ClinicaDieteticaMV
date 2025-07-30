import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../service/Auth-Service/Auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterModule],
  templateUrl: './login.component.html',
})
export class LoginComponent implements OnInit {
  @ViewChild('emailInput') emailInput!: ElementRef;
  @ViewChild('passwordInput') passwordInput!: ElementRef;
  loginForm: FormGroup;
  errorMessage: string = '';
  loading = false;
  mostrarPassword = false;

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

  ngOnInit(): void {
    //Puede que el componente no este preparado cuando se llama a ngOnInit,
    //por lo que es mejor usar setTimeout para asegurarse de que el elemento esté disponible.
    setTimeout(() => {
      if (this.emailInput) {
        this.emailInput.nativeElement.focus();
      }
    }, 0);
  }

  //Método para enfocar el input en la contraseña al presionar Enter en el input de email
  focusPasswordInput(): void {
    if (this.passwordInput) {
      this.passwordInput.nativeElement.focus();
    }
  }

  onSubmit(): void {
    if (this.loginForm.invalid) return;

    this.loading = true;
    this.errorMessage = '';

    const { email, password } = this.loginForm.value;

    //========================
    // Re direccionamiento después del login
    //========================

    this.authService.login({ email, password }).subscribe({
      next: (response) => {
        //Guarda el usuario, el token ya lo hace el AuthService con tap()

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
  volverLanding() {
    this.router.navigate(['/']);
  }
}