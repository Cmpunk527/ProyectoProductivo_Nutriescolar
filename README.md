# NutriEscolar

Sistema web para la **gestión de menús escolares, registro de consumo y seguimiento nutricional** de los estudiantes del Colegio Técnico Palermo.

![Pantalla principal](evidencias/capturas/01_index.jpg)

## Descripción

Las instituciones educativas que ofrecen alimentación escolar necesitan saber qué alimentos se sirven cada día, cuál es su valor nutricional y qué consume realmente cada estudiante. NutriEscolar permite:

- **Al administrador:** registrar alimentos con su información nutricional, crear los menús por fecha y cargarles varios alimentos a la vez.
- **A estudiantes y profesores (con cuenta e inicio de sesión):** consultar el menú, marcar qué alimentos comieron y recibir por correo un reporte que indica si su consumo estuvo bajo, adecuado o alto frente a los valores de referencia de un almuerzo escolar.
- **A cualquier usuario registrado:** recuperar su contraseña mediante un enlace temporal enviado al correo.

## Integrantes

- Juan David Carrillo Leon 
- Tomas Leonardo España Muñoz
- Aiderson Julio Chiquillo

## Institución

Colegio Técnico Palermo I.E.D.

## Programa

Técnico en Programación de Software

## Tecnologías utilizadas

- XAMPP (Apache + MySQL/MariaDB + phpMyAdmin)
- HTML5
- PHP 7.4 o superior (PDO, sesiones, `password_hash`)
- CSS3 y JavaScript (validaciones de formularios en tiempo real, selección múltiple de alimentos)
- PHPMailer (incluido en `app/includes/PHPMailer/`, envío de correos por SMTP)
- Visual Studio Code

## Estructura del repositorio

```
ProyectoProductivo_NutriEscolar/
├── app/                  Código fuente del sistema (PHP, CSS, JS, imágenes)
│   ├── includes/         Conexión a la BD, sesión, correo, reporte nutricional, PHPMailer
│   ├── Estilos/          Hoja de estilos
│   ├── js/               Validaciones y selección múltiple
│   └── Imagenes/         Recursos gráficos
├── database/
│   └── nutriescolar.sql  Script único de la base de datos (estructura + datos de ejemplo)
├── docs/                 Documento Base, Manual Técnico y Manual de Usuario
├── evidencias/           Capturas del sistema y resultado de las pruebas
├── README.md
└── .gitignore
```

## Base de datos

El script se encuentra en [`database/nutriescolar.sql`](database/nutriescolar.sql). Es un **único script** que sirve para instalar desde cero o para actualizar una base ya existente (no borra datos y se puede ejecutar varias veces). Crea la base `PROYECTO` con 14 tablas, índices y datos de ejemplo ficticios.

Tablas principales: `Usuario`, `Rol`, `Estudiante`, `Acudiente`, `Curso`, `Grado`, `Alimento`, `InformacionNutricional`, `Menu`, `MenuAlimento`, `ConsumoAlimento`, `RecuperacionContrasena`.

## Cómo ejecutar el proyecto

1. Instalar [XAMPP](https://www.apachefriends.org/) e iniciar **Apache** y **MySQL** desde el panel de control.
2. Copiar **el contenido de la carpeta `app/`** dentro de `C:\xampp\htdocs\nutriescolar\`.
3. Abrir `http://localhost/phpmyadmin`, ir a **Importar**, elegir `database/nutriescolar.sql` y pulsar **Continuar** (no hace falta crear la base antes).
4. Configuración local de la base de datos (opcional): por defecto se usa `root` sin contraseña, como en XAMPP. Si tu MySQL es distinto, copia `app/includes/config.local.example.php` como `app/includes/config.local.php` y ajusta los datos.
5. Abrir **http://localhost/nutriescolar/**

### Envío de correos (opcional)

La recuperación de contraseña y el reporte nutricional envían correos por SMTP. Para activarlo, copia `app/includes/config.local.example.php` como `app/includes/config.local.php` y escribe tu correo y una *contraseña de aplicación* de Gmail ([cómo obtenerla](https://myaccount.google.com/apppasswords)). Ese archivo **no se sube a GitHub** (está en `.gitignore`). Sin esa configuración el sistema funciona igual y simplemente informa que no pudo enviar el correo.

### Usuarios de ejemplo (solo para pruebas)

| Rol | Usuario | Contraseña |
|-----|---------|------------|
| Administrador | `admin` | `admin123` |
| Estudiante | `juan perez` | `estudiante123` |

Son cuentas ficticias creadas por el script SQL; cámbialas si algún día se publica el sistema.

## Pruebas realizadas

El sistema se probó de punta a punta (registro, inicio de sesión por rol, CRUD de alimentos y menús, consumo, recuperación de contraseña) con PHP 8.3 + MariaDB 10.11 y un navegador automatizado: **40 de 40 pruebas correctas**. Detalle en [`evidencias/resultado_pruebas_e2e.txt`](evidencias/resultado_pruebas_e2e.txt) y capturas en [`evidencias/capturas/`](evidencias/capturas/).

## Documentación

- [Documento Base](docs/Proyecto_Productivo_Modelo.docx)
- [Manual Técnico](docs/Manual_Tecnico.docx)
- [Manual de Usuario](docs/Manual_Usuario.docx)

## Año

2026
