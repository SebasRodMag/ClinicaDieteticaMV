<<<<<<< HEAD
import { bootstrapApplication } from '@angular/platform-browser';
import { provideHttpClient, HTTP_INTERCEPTORS } from '@angular/common/http';
import { AuthInterceptor } from './app/interceptor/auth.interceptor';
import { AppComponent } from './app/app.component';
import { appConfig } from './app/app.config';

bootstrapApplication(AppComponent, {
  ...appConfig,
  providers: [
    ...(appConfig.providers ?? []),
    provideHttpClient(),
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true,
    },
  ],
=======
// src/main.ts
import { bootstrapApplication } from '@angular/platform-browser';
import { AppComponent } from './app/app.component';
import { provideRouter } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';
import { routes } from './app/app.routes';
/**
 * Punto de entrada de la aplicación Angular.
 * Inicializa la aplicación y configura el enrutamiento y el cliente HTTP.
 * @returns {Promise<void>} Promesa que se resuelve cuando la aplicación está lista.
 * @throws {Error} Si ocurre un error durante el arranque de la aplicación.
 */
bootstrapApplication(AppComponent, {
  providers: [provideHttpClient(), provideRouter(routes)],
>>>>>>> 421cfda064b38e409ce148c920dde9c6b4da21f5
}).catch(err => console.error(err));
