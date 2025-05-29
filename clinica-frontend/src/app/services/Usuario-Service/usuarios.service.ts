import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class UsuariosService {
  private usuariosUrl = 'http:/localhost:8000/usuarios';
  constructor(private http: HttpClient) {}

  
}
