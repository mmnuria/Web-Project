<?php
// includes/funciones.php

/**
 * Formatea una hora en formato H:i:s a H:i (quita los segundos)
 * @param string $hora Hora en formato "HH:MM:SS"
 * @return string Hora formateada sin segundos "HH:MM"
 */
function formatHoraSinSegundos($hora)
{
    if (!$hora) {
        return '';
    }
    $dt = DateTime::createFromFormat('H:i:s', $hora);
    return $dt ? $dt->format('H:i') : $hora;
}
