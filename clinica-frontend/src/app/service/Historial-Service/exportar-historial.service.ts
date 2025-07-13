import { Injectable } from '@angular/core';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as Papa from 'papaparse';
import { saveAs } from 'file-saver';
import { Historial } from '../../models/historial.model';
import { UserService } from '../User-Service/user.service';
import { ConfiguracionService } from '../Config-Service/configuracion.service';
import { formatearFecha } from '../../components/utilidades/sanitizar.utils';
import { take } from 'rxjs/operators';

@Injectable({ providedIn: 'root' })
export class ExportadorHistorialService {
    constructor(
        private UserService: UserService,
        private configuracionService: ConfiguracionService
    ) { }

    exportarPDF(historial: Historial, idEspecialista: number): void {
        this.UserService.obtenerEspecialistaPorId(idEspecialista).subscribe({
            next: (especialista) => {
                this.configuracionService.colorTema$.pipe(take(1)).subscribe(colorHex => {
                    this.generarPDF(historial, especialista, colorHex);
                });
            },
            error: () => {
                console.warn('No se pudo obtener el especialista');
            }
        });
    }

    exportarCSV(historial: Historial): void {
        const nombrePaciente = `${historial.paciente?.user?.nombre ?? ''} ${historial.paciente?.user?.apellidos ?? ''}`.trim();
        const datos = [
            ['Clínica Dietética - Historial del Paciente'],
            [`Fecha: ${formatearFecha(new Date().toISOString())}`],
            [''],
            ['Fecha', 'Paciente', 'Observaciones', 'Recomendaciones', 'Dieta', 'Lista de compra'],
            [
                historial.fecha ?? '',
                nombrePaciente,
                historial.observaciones_especialista ?? '',
                historial.recomendaciones ?? '',
                historial.dieta ?? '',
                historial.lista_compra ?? ''
            ]
        ];

        const csv = Papa.unparse(datos, { quotes: true });
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const nombreArchivo = `Historial_${nombrePaciente}_${new Date().toISOString().slice(0, 10)}.csv`;
        saveAs(blob, nombreArchivo);
    }

    // debe ser async para 
    private async generarPDF(historial: Historial, especialista: any, colorHex: string): Promise<void> {
        const doc = new jsPDF();
        const colorRGB = this.hexToRgb(colorHex);
        let y = 45;

        const logoBase64 = await this.convertirImagenABase64('assets/images/Imagen1.png');
        doc.addImage(logoBase64, 'PNG', 14, 10, 30, 30);

        doc.setFont('times', 'bold');
        doc.setFontSize(16);
        doc.text('Clínica Dietética - Historial del Paciente', 105, 20, { align: 'center' });

        const fechaHoy = new Date();
        const fechaFormateada = `${fechaHoy.getDate().toString().padStart(2, '0')}/${(fechaHoy.getMonth() + 1).toString().padStart(2, '0')}/${fechaHoy.getFullYear()}`;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.text(`Fecha de exportación del historial: ${fechaFormateada}`, 180, 30, { align: 'right' });

        const campos = [
            { label: 'Fecha', valor: formatearFecha(historial.fecha ?? '') },
            { label: 'Paciente', valor: `${historial.paciente?.user?.nombre ?? ''} ${historial.paciente?.user?.apellidos ?? ''}`.trim() },
            { label: 'Observaciones', valor: historial.observaciones_especialista ?? '' },
            { label: 'Recomendaciones', valor: historial.recomendaciones ?? '' },
            { label: 'Dieta', valor: historial.dieta ?? '' },
            { label: 'Lista de la compra', valor: historial.lista_compra ?? '' }
        ];

        doc.setFontSize(11);
        campos.forEach(campo => {
            doc.setFillColor(...colorRGB);
            doc.setTextColor(255);
            doc.setFont('helvetica', 'bold');
            doc.rect(14, y, 180, 7, 'F');
            doc.text(campo.label, 16, y + 5);

            y += 12;

            doc.setFont('helvetica', 'normal');
            doc.setTextColor(0);
            const contenido = doc.splitTextToSize(campo.valor, 180);
            doc.text(contenido, 18, y);
            y += contenido.length * 6 + 6;
        });

        // Firma
        const firmaY = 250;
        const nombreEspecialista = `${especialista.user?.nombre ?? ''} ${especialista.user?.apellidos ?? ''}`.trim();
        const especialidad = especialista.especialidad ?? '';

        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.line(140, firmaY, 195, firmaY);
        doc.text(nombreEspecialista, 167.5, firmaY + 5, { align: 'center' });
        if (especialidad) {
            doc.text(especialidad, 167.5, firmaY + 10, { align: 'center' });
            doc.text('Firma del especialista', 167.5, firmaY + 15, { align: 'center' });
        } else {
            doc.text('Firma del especialista', 167.5, firmaY + 10, { align: 'center' });
        }

        const pageCount = (doc.internal as any).getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setFontSize(9);
            doc.text(`Página ${i} de ${pageCount}`, 105, 290, { align: 'center' });
            doc.text('Este documento es confidencial y exclusivo de Clínica Dietética', 105, 285, { align: 'center' });
        }

        const nombrePaciente = `${historial.paciente?.user?.nombre ?? 'paciente'}`;
        const nombreArchivo = `Historial_${nombrePaciente}_${fechaHoy.toISOString().slice(0, 10)}.pdf`;
        doc.save(nombreArchivo);
    }

    private convertirImagenABase64(ruta: string): Promise<string> {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.src = ruta;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                if (!ctx) return reject('No se pudo obtener el contexto');
                ctx.drawImage(img, 0, 0);
                const base64 = canvas.toDataURL('image/png');
                resolve(base64);
            };
            img.onerror = () => reject('No se pudo cargar la imagen');
        });
    }

    private hexToRgb(hex: string): [number, number, number] {
        const bigint = parseInt(hex.replace('#', ''), 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return [r, g, b];
    }
}
