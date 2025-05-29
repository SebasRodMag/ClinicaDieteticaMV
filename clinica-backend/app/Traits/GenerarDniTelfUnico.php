<?php

namespace App\Traits;

use App\Models\User;

trait GenerarDniTelfUnico
{
    /**
     * Genera un DNI válido aleatorio.
     * El formato es 8 dígitos seguidos de una letra.
     * El número se genera aleatoriamente y la letra se calcula
     * a partir del número usando el algoritmo del DNI español.
     */
    public function generarDni(): string
    {
        $numero = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
        $pos = intval($numero) % 23;
        $letra = $letras[$pos];
        return $numero . $letra;
    }

    /**
     * Genera un teléfono válido aleatorio.
     * El formato es un número que comienza con 6 o 7,
     * seguido de 8 dígitos.
     */
    public function generarTelefono(): string
    {
        $prefijo = rand(6, 7);
        $numero = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        return $prefijo . $numero;
    }

    /**
     * Comprueba en la tabla users que el dni no exista.
     * Genera un DNI único.
     * Si el DNI ya existe, genera uno nuevo hasta encontrar uno único. 
     */
    public function generarDniUnico(): string
    {
        do {
            $dni = $this->generarDni();
            $existe = User::where('dni_usuario', $dni)->exists();
        } while ($existe);

        return $dni;
    }

    /**
     * Comprueba en la tabla users que el teléfono no exista.
     * Genera un teléfono único.
     * Si el teléfono ya existe, genera uno nuevo hasta encontrar uno único.
     */
    public function generarTelefonoUnico(): string
    {
        do {
            $telefono = $this->generarTelefono();
            $existe = User::where('telefono', $telefono)->exists();
        } while ($existe);

        return $telefono;
    }
}
