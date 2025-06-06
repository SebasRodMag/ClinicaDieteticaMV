<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'configuracion';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Aquí es donde Laravel puede ayudarte a manejar el tipo de datos
        // automáticamente. Por ejemplo, si 'dias_no_laborables' es JSON,
        // lo puedes castear como array.
        'valor' => 'string', // Por defecto, se trata como string
    ];

    /**
     * Get a configuration value by its key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::where('clave', $key)->first();

        if (!$config) {
            return $default;
        }

        // Intenta decodificar JSON si el valor parece ser JSON.
        // Esto permite flexibilidad sin tener que castear explícitamente cada clave en $casts.
        $decodedValue = json_decode($config->valor, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedValue)) {
            return $decodedValue;
        }

        // Si el valor es una cadena 'true' o 'false', conviértelo a booleano.
        if (strtolower($config->valor) === 'true') {
            return true;
        }
        if (strtolower($config->valor) === 'false') {
            return false;
        }

        // Si el valor es numérico, conviértelo a int o float.
        if (is_numeric($config->valor)) {
            return strpos($config->valor, '.') !== false ? (float) $config->valor : (int) $config->valor;
        }

        return $config->valor;
    }

    /**
     * Set a configuration value by its key.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return self
     */
    public static function set(string $key, mixed $value, ?string $description = null): self
    {
        // Si el valor es un array o booleano, conviértelo a JSON o string
        if (is_array($value) || is_bool($value)) {
            $value = json_encode($value);
        }

        return self::updateOrCreate(
            ['clave' => $key],
            ['valor' => $value, 'descripcion' => $description]
        );
    }
}