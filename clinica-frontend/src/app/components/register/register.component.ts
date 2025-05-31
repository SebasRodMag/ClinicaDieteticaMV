import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, FormControl, Validators, AbstractControl } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../service/Auth-Service/Auth.service';
import { ToastrService } from 'ngx-toastr';


// Interfaz con tipos explícitos para los controles del formulario
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
  imports: [CommonModule, ReactiveFormsModule],
})
export class RegisterComponent {
  registerForm: FormGroup;
  errorMessages: { [key: string]: string[] } = {};
  loading = false;

  constructor(private fb: FormBuilder, private authService: AuthService, private router: Router) {
    this.registerForm = this.fb.group({
      nombre: ['', [Validators.required, Validators.minLength(2)]],
      apellidos: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      dni_usuario: ['', [Validators.required, Validators.pattern(/^[0-9]{8}[A-Za-z]$/)]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      password_confirmation: ['', [Validators.required]],
    }, { validators: this.confirmarContraseña });
  }

  // Getter tipado para evitar errores en el template
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

    this.authService.register(this.registerForm.value).subscribe({
      next: () => {
        this.loading = false;
        this.router.navigate(['/']);
      },
      error: (error) => {
        this.loading = false;
        this.errorMessages = error.error?.errors || { general: ['Error en el registro'] };
        console.error('[Register] Error:', error);
      }
    });
  }
}
