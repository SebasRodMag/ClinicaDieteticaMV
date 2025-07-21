import { sanitizarTexto } from './sanitizar.utils';

/**
 * Crea un nuevo FormData con todos los campos de texto sanitizados.
 * Elimina etiquetas <script>, HTML y espacios innecesarios.
 * No modifica archivos (tipo File o Blob).
 */
export function sanitizarTextoFormData(formData: FormData): FormData {
    const nuevoForm = new FormData();

    formData.forEach((valor, clave) => {
        if (typeof valor === 'string') {
            nuevoForm.append(clave, sanitizarTexto(valor));
        } else {
            nuevoForm.append(clave, valor); // Archivos o blobs
        }
    });

    return nuevoForm;
}
