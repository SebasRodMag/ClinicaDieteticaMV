import { Component, OnInit, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, FormControl, Validators, AbstractControl } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../service/Auth-Service/Auth.service';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';

type RegisterPayload = {
  nombre: string;
  apellidos: string;
  email: string;
  dni_usuario: string;
  password: string;
  password_confirmation: string;
};

interface RegisterFormControls {
  nombre: FormControl<string | null>;
  apellidos: FormControl<string | null>;
  email: FormControl<string | null>;
  password: FormControl<string | null>;
  password_confirmation: FormControl<string | null>;
  dni_usuario: FormControl<string | null>;
}

@Component({
  standalone: true,
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css'],
  imports: [CommonModule, ReactiveFormsModule, MatSnackBarModule, RouterModule],
})
export class RegisterComponent implements OnInit, AfterViewInit {

  @ViewChild('nombreInput') nombreInput!: ElementRef;
  @ViewChild('apellidosInput') apellidosInput!: ElementRef;
  @ViewChild('emailInput') emailInput!: ElementRef;
  @ViewChild('dniInput') dniInput!: ElementRef;
  @ViewChild('passwordInput') passwordInput!: ElementRef;
  @ViewChild('passwordConfirmationInput') passwordConfirmationInput!: ElementRef;

  private mapaCampos: { [key: string]: ElementRef | undefined } = {};

  registerForm: FormGroup;
  errorMessages: { [key: string]: string[] } = {};
  loading = false;
  mostrarPassword = false;
  mostrarConfirmarPassword = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {
    this.registerForm = this.fb.group(
      {
        nombre: ['', [Validators.required, Validators.minLength(2)]],
        apellidos: ['', [Validators.required, Validators.minLength(2)]],
        email: ['', [Validators.required, Validators.email]],
        dni_usuario: ['', [Validators.required, Validators.pattern(/^[0-9]{8}[A-Za-z]$/)]],
        password: ['', [Validators.required, Validators.minLength(6)]],
        password_confirmation: ['', [Validators.required]],
      },
      { validators: this.confirmarContraseña }
    );
  }

  ngOnInit(): void {
    // Para limpia errores al escribir
    this.registerForm.valueChanges.subscribe(() => {
      this.errorMessages = {};
    });

    //Enfocar primer campo
    setTimeout(() => {
      if (this.nombreInput) this.nombreInput.nativeElement.focus();
    }, 0);
  }

  ngAfterViewInit(): void {
    this.mapaCampos = {
      nombre: this.nombreInput,
      apellidos: this.apellidosInput,
      email: this.emailInput,
      dni_usuario: this.dniInput,
      password: this.passwordInput,
      password_confirmation: this.passwordConfirmationInput,
    };
  }

  //Enfoca siguiente campo
  focusCampoSig(fieldName: string): void {
    const nextField = this.mapaCampos[fieldName];
    if (nextField?.nativeElement) nextField.nativeElement.focus();
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

    //Payload para crear usuario
    const payload: RegisterPayload = {
      nombre: this.formulario['nombre'].value ?? '',
      apellidos: this.formulario['apellidos'].value ?? '',
      email: this.formulario['email'].value ?? '',
      dni_usuario: this.formulario['dni_usuario'].value ?? '',
      password: this.formulario['password'].value ?? '',
      password_confirmation: this.formulario['password_confirmation'].value ?? '',
    };

    this.authService.register(payload).subscribe({
      next: (resp: any) => {
        this.loading = false;

        if (resp?.access_token && resp?.user) {
          this.snackBar.open('Registro exitoso. ¡Bienvenido!', 'Cerrar', {
            duration: 3000,
            panelClass: ['snackbar-success'],
          });
          this.router.navigate(['/']);
          return;
        }

        //si el backend NO devuelve token, redirige a login
        this.snackBar.open('Registro exitoso. Ahora inicia sesión.', 'Cerrar', {
          duration: 3000,
          panelClass: ['snackbar-success'],
        });
        this.router.navigate(['/login']);
      },
      error: (error) => {
        this.loading = false;
        console.error('[Register] Error:', error);

        //Para mostrar errores de validación 422 bajo los campos
        if (error?.error?.errors) {
          this.errorMessages = error.error.errors;
        }

        // Para mostrar los errores generales del backend
        this.snackBar.open(
          error?.error?.message || 'Los datos proporcionados no son válidos.',
          'Cerrar',
          { duration: 4000, panelClass: ['snackbar-error'] }
        );
      }
    });
  }

  devolverError(): boolean {
    return this.errorMessages && Object.keys(this.errorMessages).length > 0;
  }
}
