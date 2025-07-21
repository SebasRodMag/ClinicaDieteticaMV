import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DocumentoService } from '../service/Documento-Service/documento.service';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { TablaDocumentosComponent } from '../components/tabla_documentos/tabla-documentos.component';
import { sanitizarTextoFormData } from '../components/utilidades/sanitizar-formadata.utils';

@Component({
    selector: 'app-paciente-documentos',
    standalone: true,
    imports: [CommonModule, FormsModule, MatSnackBarModule, TablaDocumentosComponent],
    templateUrl: './paciente-documentos.component.html',
})
export class PacienteDocumentosComponent {
    nombre: string = '';
    descripcion: string = '';
    archivo: File | null = null;

    documentos: any[] = [];
    cargandoLista: boolean = false;
    mostrarFormulario: boolean = false;
    subiendo: boolean = false;
    cargandoEliminacion: boolean = false;

    ngOnInit(): void {
        this.cargarMisDocumentos();
    }

    constructor(private docService: DocumentoService, private snackBar: MatSnackBar) { }

    onArchivoSeleccionado(event: Event): void {
        const input = event.target as HTMLInputElement;
        if (input.files && input.files.length > 0) {
            this.archivo = input.files[0];
        }
    }

    cargarMisDocumentos(): void {
        this.cargandoLista = true;
        this.docService.obtenerMisDocumentos().subscribe({
            next: (resp) => {
                this.documentos = resp.documentos;
                this.cargandoLista = false;
            },
            error: () => {
                this.documentos = [];
                this.cargandoLista = false;
            },
        });
    }

    eliminar(doc: any): void {
        const snackBarRef = this.snackBar.open('¿Eliminar este documento?', 'Sí', {
            duration: 5000, //se cierra después de 5 segundos si no se confirma
            verticalPosition: 'top',
        });

        this.cargandoEliminacion = true;
        snackBarRef.onAction().subscribe(() => {
            this.docService.eliminarDocumento(doc.id).subscribe({
            next: () => {
                this.snackBar.open('Documento eliminado', 'Cerrar', { duration: 3000 });
                this.cargarMisDocumentos();
                this.cargandoEliminacion = false;
            },
            error: () => {
                this.snackBar.open('Error al eliminar el documento', 'Cerrar', { duration: 3000 });
                this.cargandoEliminacion = false;
            }
        });
        });
        
    }

    descargar(doc: any): void {
        this.docService.descargarDocumento(doc.id).subscribe({
            next: (blob) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = doc.nombre;
                a.click();
                window.URL.revokeObjectURL(url);
            },
            error: () => {
                this.snackBar.open('No se pudo descargar el documento', 'Cerrar', { duration: 3000 });
            }
        });
    }

    enviarDocumento(): void {
        if (!this.archivo) return;

        this.subiendo = true;

        const formData = new FormData();
        formData.append('nombre', this.nombre);
        formData.append('descripcion', this.descripcion);
        formData.append('archivo', this.archivo);

        this.docService.subirDocumento(formData).subscribe({
            next: () => {
                this.snackBar.open('Documento subido correctamente', 'Cerrar', { duration: 3000 });
                this.limpiarFormulario();
                this.cargarMisDocumentos();
                this.subiendo = false;
            },
            error: () => {
                this.snackBar.open('Error al subir el documento', 'Cerrar', { duration: 3000 });
                this.subiendo = false;
            },
        });
    }

    limpiarFormulario(): void {
        this.nombre = '';
        this.descripcion = '';
        this.archivo = null;
        this.mostrarFormulario = false;
    }
}
