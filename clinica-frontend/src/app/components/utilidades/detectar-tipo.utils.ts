/**
 * Se verifican los tipo de datos que se van a mostrar en la vista 
 * para que al mostrarlo en la vista se pueda mostrar de la forma correcta
 * 
 */
export type TipoValorConfiguracion =
    | 'color'
    | 'boolean'
    | 'array'
    | 'objeto'
    | 'numero'
    | 'texto';

export function detectarTipoValor(valor: any): TipoValorConfiguracion {
    if (typeof valor === 'string' && /^#([0-9A-F]{3}){1,2}$/i.test(valor)) {
        return 'color';
    }
    if (typeof valor === 'boolean') return 'boolean';
    if (Array.isArray(valor)) return 'array';
    if (typeof valor === 'object' && valor !== null) return 'objeto';
    if (typeof valor === 'number') return 'numero';
    return 'texto';
}