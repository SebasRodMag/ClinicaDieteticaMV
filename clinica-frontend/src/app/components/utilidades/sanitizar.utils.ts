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
 * Formatea fecha de '2025-06-19' a 'DD/MM/YYYY' por defecto,
 * y permite cambiar el separador (p. ej. '-').
 */
export function formatearFecha(fechaIso: string, sep: '-' | '/' = '/'): string {
    if (!fechaIso) return '';
    // Acepta también 'YYYY-MM-DDTHH:mm:ss' y se queda con la parte de fecha
    const soloFecha = fechaIso.slice(0, 10);
    const m = soloFecha.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return fechaIso;
    const [, y, mm, dd] = m;
    return `${dd}${sep}${mm}${sep}${y}`;
}

/**
 * Convierte 'DD-MM-YYYY' o 'DD/MM/YYYY' a 'YYYY-MM-DD'.
 * Si ya viene en ISO 'YYYY-MM-DD', lo deja igual.
 */
export function convertirFechaAISO(fecha: string): string {
    if (!fecha) return fecha;

    //Ya es ISO (YYYY-MM-DD)
    if (/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return fecha;

    //DD-MM-YYYY o DD/MM/YYYY
    const m = fecha.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/);
    if (m) {
        const [, dd, mm, yyyy] = m;
        return `${yyyy}-${mm}-${dd}`;
    }

    //Si no reconoce el formato, devuelve el original para no romper flujos
    return fecha;
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

/**
 * Devuelve la fecha local (no UTC) a partir de fecha y hora.
 * Acepta fecha 'YYYY-MM-DD', 'DD-MM-YYYY' o 'DD/MM/YYYY'.
 * Hora opcional en 'HH:mm' o 'HH:mm:ss'.
 */
export function construirFechaHoraLocal(fecha: string, hora?: string): Date {
    const iso = convertirFechaAISO(fecha);
    const hhmm = (hora ?? '00:00').slice(0, 5);
    const m = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return new Date(NaN);
    const [, y, mm, dd] = m.map(Number);
    const [h, min] = hhmm.split(':').map(Number);
    return new Date(y, (mm - 1), dd, h || 0, min || 0, 0, 0);
}
