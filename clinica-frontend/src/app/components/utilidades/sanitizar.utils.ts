/**
 * Elimina etiquetas <script> y HTML, y recorta espacios.
 * Previene intentos de XSS y basura HTML en campos de texto.
 */
export function sanitizarTexto(texto: string): string {
    return texto
        .replace(/<script.*?>.*?<\/script>/gi, '') //elimina <script>
        .replace(/<\/?[^>]+(>|$)/g, '')            //elimina etiquetas HTML
        .trim();                                   //elimina espacios
}

/**
 * Valida formato de email estándar.
 */
export function validarEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email.trim());
}

/**
 * Formatea fecha de '2025-06-19' a 'DD/MM/YYYY'.
 */
export function formatearFecha(fechaIso: string): string {
    const fecha = new Date(fechaIso);
    const dia = fecha.getDate().toString().padStart(2, '0');
    const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
    const año = fecha.getFullYear();
    return `${dia}/${mes}/${año}`;
}

/**
 * Formatea una hora de '2025-06-19T14:30:00' a 'HH:mm'.
 */
export function formatearHora(fechaHora: string): string {
    const fecha = new Date(fechaHora);
    const horas = fecha.getHours().toString().padStart(2, '0');
    const minutos = fecha.getMinutes().toString().padStart(2, '0');
    return `${horas}:${minutos}`;
}

/**
 * Valida un DNI o NIE comprobando la letra de control.
 */
export function validarDniNie(identificador: string): boolean {
    let esValido = false;

    if (identificador) {
        const letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
        const valor = identificador.toUpperCase().replace(/\s/g, '');

        let numero = 0;
        let letraControl = '';
        let letraCalculada = '';

        const esNie = /^[XYZ]\d{7}[A-Z]$/.test(valor);
        const esDni = /^\d{8}[A-Z]$/.test(valor);

        if (esNie) {
            const conversion: Record<'X' | 'Y' | 'Z', string> = { X: '0', Y: '1', Z: '2' };
            const letraInicial = valor[0] as 'X' | 'Y' | 'Z';
            const digitos = valor.slice(1, -1);
            letraControl = valor.slice(-1);
            numero = parseInt(conversion[letraInicial] + digitos, 10);
            letraCalculada = letras[numero % 23];
            esValido = letraCalculada === letraControl;
        }

        if (esDni) {
            numero = parseInt(valor.slice(0, 8), 10);
            letraControl = valor.slice(-1);
            letraCalculada = letras[numero % 23];
            esValido = letraCalculada === letraControl;
        }
    }

    return esValido;
}

