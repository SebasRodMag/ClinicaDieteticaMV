import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, FormControl, Validators, AbstractControl } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
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
  imports: [CommonModule, ReactiveFormsModule, MatSnackBarModule, RouterModule],
})
export class RegisterComponent implements OnInit {

  //Se declaran los ViewChild para cada input que quiere enfocar
  @ViewChild('nombreInput') nombreInput!: ElementRef;
  @ViewChild('apellidosInput') apellidosInput!: ElementRef;
  @ViewChild('emailInput') emailInput!: ElementRef;
  @ViewChild('dniInput') dniInput!: ElementRef;
  @ViewChild('passwordInput') passwordInput!: ElementRef;
  @ViewChild('passwordConfirmationInput') passwordConfirmationInput!: ElementRef;

  //Se crea un mapa para asociar los nombres de los campos con sus referencias de ElementRef
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

    //Por las dudas que los inputs no estén cargados al momento de ejecutar ngOnInit,
    //usamos setTimeout para darle un margen de tiempo hasta que los elementos estén disponibles.
    setTimeout(() => {
      if (this.nombreInput) {
        this.nombreInput.nativeElement.focus();
      }
    }, 0);
  }

  ngAfterViewInit(): void {
    //Se asignan las referencias al mapa de mapaCampos para acceder a los inputs más fácil
    this.mapaCampos = {
      nombre: this.nombreInput,
      apellidos: this.apellidosInput,
      email: this.emailInput,
      dni_usuario: this.dniInput,
      password: this.passwordInput,
      password_confirmation: this.passwordConfirmationInput,
    };
  }

  //Método para enfocar el campo siguiente
  focusCampoSig(fieldName: string): void {
    const nextField = this.mapaCampos[fieldName];
    if (nextField && nextField.nativeElement) {
      nextField.nativeElement.focus();
    }
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
  devolverError(): boolean {
    return this.errorMessages && Object.keys(this.errorMessages).length > 0;
  }
}