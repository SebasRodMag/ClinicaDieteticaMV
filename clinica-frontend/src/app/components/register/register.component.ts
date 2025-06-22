import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, FormControl, Validators, AbstractControl } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../service/Auth-Service/Auth.service';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';


//Interfaz con tipos explícitos para los controles del formulario
interface RegisterFormControls {
  nombre: FormControl;
  apellidos: FormControl;
  email: FormControl;
  password: FormControl;
  password_confirmation: FormControl;
  dni_usuario: FormControl;

}
@Component({
  standalone: true,
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css'],
  imports: [CommonModule, ReactiveFormsModule, MatSnackBarModule],
})
export class RegisterComponent implements OnInit {
  registerForm: FormGroup;
  errorMessages: { [key: string]: string[] } = {};
  loading = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {
    this.registerForm = this.fb.group({
      nombre: ['', [Validators.required, Validators.minLength(2)]],
      apellidos: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      dni_usuario: ['', [Validators.required, Validators.pattern(/^[0-9]{8}[A-Za-z]$/)]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      password_confirmation: ['', [Validators.required]],
    }, { validators: this.confirmarContraseña });
  }

  ngOnInit(): void {
    // Limpia los errores cuando el formulario cambia
    this.registerForm.valueChanges.subscribe(() => {
      this.errorMessages = {};
    });
  }

  get formulario(): RegisterFormControls {
    return this.registerForm.controls as unknown as RegisterFormControls;
  }

  confirmarContraseña(group: AbstractControl) {
    const pass = group.get('password')?.value;
    const confirm = group.get('password_confirmation')?.value;
    return pass === confirm ? null : { notMatching: true };
  }

  onSubmit(): void {
    if (this.registerForm.invalid) {
      this.registerForm.markAllAsTouched();
      return;
    }

    this.loading = true;
    this.errorMessages = {};

    this.authService.register(this.registerForm.value).subscribe({
      next: () => {
        this.loading = false;
        this.snackBar.open('Registro exitoso. ¡Bienvenido!', 'Cerrar', {
          duration: 3000,
          panelClass: ['snackbar-success'],
        });
        this.router.navigate(['/']);
      },
      error: (error) => {
        this.loading = false;
        console.error('[Register] Error:', error);

        if (error.error?.errors) {
          this.errorMessages = error.error.errors;

          if (this.errorMessages['general']) {
            this.snackBar.open(this.errorMessages['general'][0], 'Cerrar', {
              duration: 4000,
              panelClass: ['snackbar-error'],
            });
          }
        } else {
          this.snackBar.open('Error inesperado al registrarse.', 'Cerrar', {
            duration: 4000,
            panelClass: ['snackbar-error'],
          });
        }
      }
    });
  }
  isErrorObjectNotEmpty(): boolean {
    return this.errorMessages && Object.keys(this.errorMessages).length > 0;
  }
}