<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cita cancelada</title>
</head>
<body>
    <h2>Hola {{ $datos['nombre'] }}</h2>
    <p>Lamentamos informarte que tu cita con el especialista {{ $datos['especialista'] }} ha sido cancelada.</p>
    <p>Fecha original: {{ $datos['fecha'] }}</p>
    <p>Si tienes dudas, contáctanos.</p>
    <br>
    <p>— Clínica Dietética</p>
</body>
</html>
