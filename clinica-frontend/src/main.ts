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
}).catch(err => console.error(err));
