import { bootstrapApplication } from '@angular/platform-browser';
import { provideHttpClient, HTTP_INTERCEPTORS } from '@angular/common/http';
import { AuthInterceptor } from './app/interceptor/auth.interceptor';
import { AppComponent } from './app/app.component';
import { appConfig } from './app/app.config';
import { ErrorInterceptor } from './app/interceptor/error.interceptor';
import { ToastrModule, provideToastr } from 'ngx-toastr';


bootstrapApplication(AppComponent, {
  ...appConfig,
  providers: [
    provideAnimations(),
    provideToastr(),
    ...(appConfig.providers ?? []),
    provideHttpClient(withInterceptors([AuthInterceptor, ErrorInterceptor])),
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true,
      
    },
    
  ],
}).catch(err => console.error(err));
