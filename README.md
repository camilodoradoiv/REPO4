# Gestión de artículos (PHP + MySQL)

Aplicación para registrar y administrar artículos con nombre, marca, unidades y ubicación.

## Funciones
- Crear, listar, buscar, editar y eliminar artículos.
- Mostrar cantidad de registros y suma de unidades.
- Crear automáticamente la base de datos y la tabla si el usuario MySQL tiene permisos.

## Requisitos
- PHP 8.1+ recomendado.
- Extensiones PHP `pdo_mysql` y `mbstring`.
- MySQL/MariaDB y servidor web (Apache/Nginx).

## Instalación
1. Descomprime el ZIP.
2. Sube los archivos a una carpeta del hosting, por ejemplo `public_html/articulos/`.
3. Verifica las credenciales en `config.php` o configura las variables de entorno `DB_HOST`, `DB_USER`, `DB_PASSWORD` y `DB_NAME`.
4. Abre la URL de esa carpeta en el navegador.

La aplicación intentará crear `ccdoriv_gestionarticulos` y la tabla `articulos` al iniciar.

## Permisos
El usuario MySQL debe tener permisos para crear bases de datos. Algunos hostings compartidos requieren crear la base desde su panel y asignar acceso al usuario.

## Seguridad
- Cambia la contraseña si la credencial compartida es real y se ha expuesto.
- En producción, usa variables de entorno para las credenciales y HTTPS.
- No publiques el ZIP con credenciales activas.
