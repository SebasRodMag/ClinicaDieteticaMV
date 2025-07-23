<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Configuracion
 *
 * @property int $id
 * @property string $clave
 * @property string $valor
 * @property string|null $descripcion
 *
 * Métodos estáticos:
 * @method static mixed get(string $clave, mixed $preDefinido = null)
 * @method static \App\Models\Configuracion set(string $clave, mixed $valor, ?string $descripcion = null)
 */

class Configuracion extends Model
{
    use HasFactory;

    /**
     * Esta es la tabla asociada a este modelo
     *
     * @var string
     */
    protected $table = 'configuracion';

    /**
     * atributos que se pueden asignar todos a la vez
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];

    /**
     * atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Si se envia un json, se convierte a un array, pero por defecto se trata como string
        'valor' => 'string',
    ];

    /**
     * Obtener el valor de la configuración por la clave
     *
     * @param string $clave Es el valor de la clave.
     * @param mixed $preDefinido Es el valor por defecto si no existe la clave.
     * @return mixed Devuelve el valor de la configuración.
     */
    public static function get(string $clave, mixed $preDefinido = null): mixed
    {
        $config = self::where('clave', $clave)->first();

        if (!$config) {
            return $preDefinido;
        }

        // Se intenta decodificar JSON si el valor parece ser JSON.
        $valorDecodificado = json_decode($config->valor, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($valorDecodificado)) {
            return $valorDecodificado;
        }

        //Si el valor es una cadena 'true' o 'false', lo convierte en booleano.
        if (strtolower($config->valor) === 'true') {
            return true;
        }
        if (strtolower($config->valor) === 'false') {
            return false;
        }

        //Si el valor es numérico, conviértelo a int o float.
        if (is_numeric($config->valor)) {
            return strpos($config->valor, '.') !== false ? (float) $config->valor : (int) $config->valor;
        }

        return $config->valor;
    }

    /**
     * Establezca un valor de configuración por su clave.
     *
     * @param string $clave Es el valor de la clave.
     * @param mixed $valor Es el valor de la configuración.
     * @param string|null $descripcion Es la descripción de la configuración.
     * @return self Devuelve el objeto actual.
     */
    public static function set(string $clave, mixed $valor, ?string $descripcion = null): self
    {
        // Si el valor es un array o booleano, conviértelo a JSON o string
        if (is_array($valor) || is_bool($valor)) {
            $valor = json_encode($valor);
        }

        return self::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor, 'descripcion' => $descripcion]
        );
    }
}