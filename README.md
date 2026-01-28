# Consulta de Certificados MAE

Aplicación web sencilla para consultar certificados de capacitación por identificación del cliente o número de certificado.

## Estructura del Proyecto

```
mae-consultas/
├── index.php              # Página principal con formulario de búsqueda y resultados
├── certificado.php        # Genera PDF del certificado
├── carnet.php             # Genera PDF del carnet
├── .htaccess              # Configuración de seguridad del servidor web
├── README.md              # Este archivo
├── assets/                # CSS, JS y otros recursos estáticos
├── fpdf/                  # Librería FPDF para generación de PDFs
└── app/                   # Código de la aplicación
    ├── Core/              # Configuración, base de datos y funciones
    └── Views/             # Vistas HTML
```

- Consulta por número de identificación del cliente
- Consulta por número de certificado
- Generación de certificados PDF
- Generación de carnets PDF
- Interfaz responsive con Bootstrap
- Sin requerir autenticación (pública)

## Requisitos

- PHP 8.0 o superior
- MySQL/MariaDB
- Base de datos `maewebdb` (misma que MAE v8)

## Instalación

La aplicación ya está instalada en la raíz del proyecto. Solo necesitas:

1. Configurar la conexión a la base de datos en `app/Core/config.php`
2. Asegurarse de que los directorios `assets/fotos/` y `assets/convenios/` tengan permisos de escritura si es necesario
3. Acceder a `index.php` desde el navegador

## Organización de Imágenes

Todas las imágenes del proyecto están centralizadas en **`assets/images/`**:

- `bgcerti.jpg` - Fondo para certificados PDF
- `bgcarnet.jpg` - Fondo para carnets PDF  
- `logo.png` - Logo de la empresa (disponible para futuras expansiones)

## Estructura de Archivos

- `index.php` - Página principal con formulario de búsqueda y resultados
- `certificado.php` - Genera PDF del certificado
- `carnet.php` - Genera PDF del carnet
- `fpdf/` - Librería FPDF para generación de PDFs
- `assets/` - CSS, JS y otros recursos estáticos
  - `assets/images/` - Todas las imágenes del proyecto
    - `bgcerti.jpg` - Fondo para certificados
    - `bgcarnet.jpg` - Fondo para carnets
    - `logo.png` - Logo de la empresa
- `app/` - Código de la aplicación
  - `app/Core/` - Configuración, base de datos y funciones
  - `app/Views/` - Vistas HTML
- `.htaccess` - Configuración de seguridad del servidor web

## Uso

1. Abrir `index.php` en el navegador
2. Seleccionar el tipo de búsqueda (Identificación o Certificado)
3. Ingresar el número correspondiente
4. Hacer clic en "Consultar"
5. Ver los resultados en la tabla
6. Hacer clic en "Certificado" o "Carnet" para descargar el PDF

## Seguridad

- La aplicación es pública y no requiere login
- Validación de entrada para prevenir inyección SQL
- Los PDFs se generan en tiempo real desde la base de datos
- Archivos sensibles protegidos por `.htaccess`:
  - `app/Core/config.php` - Configuración de base de datos
  - `app/Core/database.php` - Conexión PDO
  - `app/` - Código de la aplicación (bloquea acceso directo)
  - `fpdf/` - Librería FPDF (bloquea acceso directo)
- Headers de seguridad HTTP incluidos
- Deshabilitado el listado de directorios