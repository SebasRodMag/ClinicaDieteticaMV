import { importProvidersFrom } from '@angular/core';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { HttpClientModule } from '@angular/common/http';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';

export const appConfig = {
  providers: [
    importProvidersFrom(BrowserAnimationsModule),
	importProvidersFrom(HttpClientModule),
    provideRouter(routes),
  ],
};
