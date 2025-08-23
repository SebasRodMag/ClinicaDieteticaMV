import { Injectable } from '@angular/core';
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

    /**
     * 
     * Los métodos para la exportación a PDF o CSV se cargaban directamente al cargar la página aunque estos no se utilizaran.
     * Esto, hacia la página muy pesada y provocaba desbordamiento de memoria al compilar el contenedor. Para solucionarlo, se movió la llamada
     * a estos métodos al botón de exportación, de esta forma, solo se carga si es necesario. Esto es conocido como Lazy import.
     */
    // ========= PDF =========
    exportarPDF(historial: Historial | Historial[], idEspecialista?: number): void {
        const historiales = Array.isArray(historial) ? historial : [historial];

        this.configuracionService.colorTema$.pipe(take(1)).subscribe((colorHex) => {
            // IIFE async para poder usar await dentro manteniendo firma void
            (async () => {
                if (idEspecialista && historiales.length === 1) {
                    try {
                        await this.UserService.obtenerEspecialistaPorId(idEspecialista).pipe(take(1)).toPromise();
                    } catch {
                        console.warn('No se pudo obtener el especialista');
                    }
                }
                await this.generarPDFLazy(historiales, colorHex);
            })();
        });
    }

    // ========= CSV =========
    exportarCSV(historial: Historial | Historial[]): void {
        const historiales = Array.isArray(historial) ? historial : [historial];

        (async () => {
            const papaMod = await import('papaparse');
            const { saveAs } = await import('file-saver');

            // Papaparse puede exportar como default o como objeto con .unparse
            const unparse =
                (papaMod as any).unparse ??
                (papaMod as any).default?.unparse ??
                null;

            if (!unparse) {
                console.error('No se pudo cargar Papaparse.unparse');
                return;
            }

            const encabezado = [
                'Fecha',
                'Paciente',
                'Especialista',
                'Especialidad',
                'Observaciones',
                'Recomendaciones',
                'Dieta',
                'Lista de compra',
            ];

            const filas = historiales.map((h) => [
                formatearFecha(h.fecha ?? ''),
                `${h.paciente?.user?.nombre ?? ''} ${h.paciente?.user?.apellidos ?? ''}`.trim(),
                `${h.especialista?.user?.nombre ?? ''} ${h.especialista?.user?.apellidos ?? ''}`.trim(),
                h.especialista?.especialidad ?? '',
                h.observaciones_especialista ?? '',
                h.recomendaciones ?? '',
                h.dieta ?? '',
                h.lista_compra ?? '',
            ]);

            const datos = [encabezado, ...filas];
            const csv: string = unparse(datos, { quotes: true });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const nombreArchivo =
                historiales.length > 1
                    ? `Historiales_${new Date().toISOString().slice(0, 10)}.csv`
                    : `Historial_${filas[0][1]}_${new Date().toISOString().slice(0, 10)}.csv`;

            saveAs(blob, nombreArchivo);
        })();
    }

    // ========= Llamadas =========

    private async generarPDFLazy(historiales: Historial[], colorHex: string): Promise<void> {
        const { jsPDF } = await import('jspdf');

        const doc = new jsPDF();
        const colorRGB = this.hexToRgb(colorHex);

        const logoBase64 = await this.convertirImagenABase64('assets/images/Imagen1.png');

        for (let i = 0; i < historiales.length; i++) {
            const historial = historiales[i];
            let y = 45;

            if (i > 0) {
                doc.addPage();
            }

            // Encabezado
            doc.addImage(logoBase64, 'PNG', 14, 10, 30, 30);
            doc.setFont('times', 'bold');
            doc.setFontSize(16);
            doc.text('Clínica Dietética - Historial del Paciente', 105, 20, { align: 'center' });
            doc.setFont('times', 'normal');
            doc.setFontSize(13);
            doc.text(
                `Paciente: ${historial.paciente?.user?.nombre ?? ''} ${historial.paciente?.user?.apellidos ?? ''}`.trim(),
                105,
                27,
                { align: 'center' }
            );

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            const fechaExport = new Date();
            doc.text(`Fecha de exportación: ${formatearFecha(fechaExport.toISOString())}`, 190, 40, { align: 'right' });

            const campos = [
                { label: 'Fecha', valor: formatearFecha(historial.fecha ?? '') },
                {
                    label: 'Paciente',
                    valor: `${historial.paciente?.user?.nombre ?? ''} ${historial.paciente?.user?.apellidos ?? ''}`.trim(),
                },
                { label: 'Observaciones', valor: historial.observaciones_especialista ?? '' },
                { label: 'Recomendaciones', valor: historial.recomendaciones ?? '' },
                { label: 'Dieta', valor: historial.dieta ?? '' },
                { label: 'Lista de la compra', valor: historial.lista_compra ?? '' },
            ];

            doc.setFontSize(11);
            for (const campo of campos) {
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
            }

            // Firma
            const firmaY = 250;
            const nombreEspecialista = `${historial.especialista?.user?.nombre ?? ''} ${historial.especialista?.user?.apellidos ?? ''}`.trim();
            const especialidad = historial.especialista?.especialidad ?? '';

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
        }

        const totalPaginas = (doc.internal as any).getNumberOfPages?.() ?? 1;
        for (let i = 1; i <= totalPaginas; i++) {
            doc.setPage(i);
            doc.setFontSize(9);
            doc.text(`Página ${i} de ${totalPaginas}`, 105, 290, { align: 'center' });
            doc.text('Este documento es confidencial y exclusivo de Clínica Dietética', 105, 285, { align: 'center' });
        }

        const nombrePaciente = `${historiales[0].paciente?.user?.nombre ?? 'paciente'}`;
        const nombreArchivo =
            historiales.length > 1
                ? `Historiales_${nombrePaciente}_${new Date().toISOString().slice(0, 10)}.pdf`
                : `Historial_${nombrePaciente}_${new Date().toISOString().slice(0, 10)}.pdf`;

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
