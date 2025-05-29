import { Component, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, RouterLink, RouterOutlet } from '@angular/router';
import { AuthService } from '../../../../auth/auth.service';

@Component({
  selector: 'app-header',
  imports: [
    CommonModule, RouterModule, RouterLink
  ],
  templateUrl: './header.component.html',
  styleUrl: './header.component.css'
})
export class HeaderComponent {
  userName: string = '';
  fechaActual = new Date();
  horaActual = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit'});
  ngOnInit(): void {
    this.fechaActual = new Date();
    setInterval(() => {
      this.horaActual = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }, 1000);
    this.userName = localStorage.getItem('name') || '';
  }

  constructor(private authService: AuthService) { }

  logout(){
    this.authService.logout();
  }
  

}
