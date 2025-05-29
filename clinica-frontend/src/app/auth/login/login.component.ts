import { Component } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators, FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { AuthService } from '../auth.service';
import { Router } from '@angular/router';

/**
 * Componente de inicio de sesión.
 * Permite a los usuarios ingresar sus credenciales para autenticarse.
 */
@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, CommonModule, FormsModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent {
  loginForm!: FormGroup;
  errorMessage: string = '';


  /**
   * Constructor del componente de inicio de sesión.
   * @param fb FormBuilder para crear formularios reactivos.
   * @param authService Servicio de autenticación para manejar el inicio de sesión.
   * @param router Router para navegar a otras rutas después del inicio de sesión.
   */
  constructor(private fb: FormBuilder, private authService: AuthService, private router: Router) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required],
    });
  }



  /**
   * Método que se ejecuta al inicializar el componente.
   * Configura el formulario de inicio de sesión con validaciones.
   * validators para los campos de email y contraseña.
   */
  ngOnInit(): void {
    // Inicializamos el formulario con validaciones
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required]
    });
  }

  /*
  * Método que se ejecuta al enviar el formulario de inicio de sesión.
  * Valida el formulario y llama al servicio de autenticación.
  * Si el inicio de sesión es exitoso, redirige al usuario a la página principal.
  * Si hay un error, muestra un mensaje de error.
  */
  onSubmit() {
    if (this.loginForm.invalid) return;
    // Llamamos al servicio de autenticación para iniciar sesión
    this.authService.login(this.loginForm.value).subscribe({
      next: () => {
        console.log('Login exitoso');
      },
      error: () => {
        this.errorMessage = 'Credenciales incorrectas';
        console.error('Error al iniciar sesión', this.errorMessage);
      }
    });
  }
}