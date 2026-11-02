# Backend interno

Esta carpeta agrupa los archivos PHP que no son páginas públicas: conexión a la
base de datos, autenticación, validación, autorización y funciones reutilizables.

Las páginas que el visitante abre permanecen en la raíz del proyecto. La carpeta
está protegida mediante `.htaccess` para que Apache no entregue directamente sus
archivos PHP.

`bootstrap.php` inicializa la sesión de forma segura y será el punto común para
conectar MySQL cuando el esquema quede definido.
