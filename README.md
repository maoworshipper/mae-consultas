# Consulta de Certificados MAE

Aplicación web sencilla para consultar certificados de capacitación por identificación del cliente o número de certificado.

## Estructura del Proyecto

```
mae-consultas/
├── index.php              # Página principal con formulario de búsqueda y resultados
├── certificado.php        # Genera PDF del certificado
├── carnet.php             # Genera PDF del carnet
├── config.php             # Configuración general
├── database.php           # Conexión a base de datos
├── .htaccess              # Configuración de seguridad del servidor web
├── README.md              # Este archivo
├── assets/                # CSS, JS y otros recursos estáticos
├── fpdf/                  # Librería FPDF para generación de PDFs
├── images/                # Imágenes de fondo para PDFs
└── legacy/                # Aplicaciones anteriores
    ├── consultas-mae/     # Versión moderna con Composer
    └── mae-v8/           # Versión legacy PHP puro
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

1. Configurar la conexión a la base de datos en `config.php`
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
- `config.php` - Configuración general
- `database.php` - Conexión a base de datos
- `fpdf/` - Librería FPDF para generación de PDFs
- `assets/` - CSS, JS y otros recursos estáticos
  - `assets/images/` - Todas las imágenes del proyecto
    - `bgcerti.jpg` - Fondo para certificados
    - `bgcarnet.jpg` - Fondo para carnets
    - `logo.png` - Logo de la empresa
- `.htaccess` - Configuración de seguridad del servidor web
- `legacy/` - Directorio con aplicaciones anteriores

## Uso

1. Abrir `index.php` en el navegador
2. Seleccionar el tipo de búsqueda (Identificación o Certificado)
3. Ingresar el número correspondiente
4. Hacer clic en "Consultar"
5. Ver los resultados en la tabla
6. Hacer clic en "Certificado" o "Carnet" para descargar el PDF

## Aplicaciones Legacy

En el directorio `legacy/` se encuentran las versiones anteriores del sistema:

- **`legacy/consultas-mae/`**: Versión moderna desarrollada con Composer, estructura MVC, y dependencias externas
- **`legacy/mae-v8/`**: Versión legacy desarrollada en PHP puro sin dependencias externas

Estas aplicaciones se mantienen como referencia y para migración gradual de datos si es necesario.

## Seguridad

- La aplicación es pública y no requiere login
- Validación de entrada para prevenir inyección SQL
- Los PDFs se generan en tiempo real desde la base de datos
- Archivos sensibles protegidos por `.htaccess`:
  - `config.php` - Configuración de base de datos
  - `database.php` - Conexión PDO
  - `fpdf/` - Librería FPDF (bloquea acceso directo)
- Headers de seguridad HTTP incluidos
- Deshabilitado el listado de directorios