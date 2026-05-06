# Pear Pay App Structure

Esta carpeta contiene la logica PHP del plugin. La idea es mantener `pearpay.php`
como archivo de arranque y separar el codigo por responsabilidad.

## Core

Inicializa los modulos principales del plugin.

## Admin

Codigo que solo existe para el panel de WordPress:

- `Pages`: pantallas del administrador.
- `Controllers`: endpoints REST usados por la interfaz admin.

## Frontend

Codigo que se ejecuta en el sitio publico:

- `Shortcodes`: shortcodes registrados por el plugin.

## Database

Tabla propia del plugin y repositorios para leer/escribir datos.

## Payments

Integraciones con pasarelas de pago. Cada proveedor debe tener su propia carpeta,
por ejemplo `Payments/Izipay`.
