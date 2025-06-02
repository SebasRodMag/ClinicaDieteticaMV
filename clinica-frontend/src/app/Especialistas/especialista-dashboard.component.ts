import { Component} from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService } from '../service/User-Service/user.service';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { EspecialistaCitasComponent } from './especialista-citas.component';

@Component({
  selector: 'app-especialista-dashboard',
  standalone: true,
  imports: [CommonModule, EspecialistaCitasComponent],
  templateUrl: './especialista-dashboard.component.html'
})
export class EspecialistaDashboardComponent {

  constructor(
    private userService: UserService,
    private authService: AuthService
  ) { }



  logout(){
    this.authService.logout();
  }
}
