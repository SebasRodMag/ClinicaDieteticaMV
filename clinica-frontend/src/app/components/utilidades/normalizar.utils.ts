/**
 * Por este medio, nos aseguramos que los valores que se envian al backend
 * sean del mismo tipo que los que se reciben.
 * 
 * @param valor Es el valor que se va a verificar
 * @returns devuelve el mismo tipo de dato que haya recibido
 */

export function normalizarValorParaGuardar(valor: any): string {
    if (typeof valor === 'boolean') {
        return valor ? 'true' : 'false';
    }

    if (typeof valor === 'object' && valor !== null) {
        return JSON.stringify(valor);
    }

    if (typeof valor === 'number') {
        return valor.toString();
    }

    return valor; // Si es string o null, se envía tal cual
}
