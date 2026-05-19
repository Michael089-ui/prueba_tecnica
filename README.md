# Sistema de Gestión de Guías de Transporte (Prueba Técnica)

Este proyecto es una solución integral y premium para la gestión y control de entregas y devoluciones de guías de transporte terrestre de mercancía. Ha sido diseñado utilizando **PHP Nativo**, **JavaScript Vanilla**, **AJAX (Fetch API)** y **MySQL**, sin el uso de frameworks, cumpliendo estrictamente con los requisitos del sector logística.

---

## 🚀 Características Principales

*   **Dashboard Moderno e Interactivo**: Panel visual responsivo con diseño *Glassmorphism* (diseño translúcido y desenfoques elegantes) y soporte móvil completo.
*   **Estadísticas en Tiempo Real**: Contadores automáticos que reflejan el total de guías en estado *PENDIENTE*, *ENTREGADO* y *DEVUELTO*.
*   **Filtros Inteligentes (Filtro por Ciudad y Estado)**: Filtrado dinámico sin recargar la página; las ciudades del selector se actualizan dinámicamente según las existentes en la base de datos.
*   **Modales de Gestión**:
    *   **Entrega**: Permite registrar el nombre de quien recibe, observaciones, firma del receptor en pantalla y foto POD (evidencia).
    *   **Devolución**: Permite seleccionar motivos tipificados (ej. dirección incorrecta, cliente ausente), observaciones, firma del transportador y foto de evidencia.
*   **Firma Digital Integrada**: Captura mediante Canvas HTML5 utilizando la API moderna `PointerEvents` para garantizar compatibilidad nativa fluida con ratón y pantallas táctiles (dispositivos móviles y tablets).
*   **Geolocalización GPS Activa**: Captura automática de coordenadas latitud/longitud en tiempo real utilizando la API del navegador con tolerancia a fallos (si el usuario deniega permisos o no hay señal, guarda `NULL` y muestra una advertencia visual sin bloquear la operación).
*   **Seguridad y Validaciones de Archivos**:
    *   *Frontend*: Valida que los campos sean correctos, firma obligatoria, peso máximo de foto de 5MB y extensiones JPG/PNG antes de enviar.
    *   *Backend (PHP)*: Validación estricta del tipo MIME de la imagen usando la extensión `finfo` (independiente de la extensión del archivo), tamaño, firma Base64 válida y coordenadas numéricas.
*   **Transaccionalidad en Base de Datos**: Actualizaciones y logs protegidos bajo transacciones SQL para asegurar la consistencia si algún guardado de archivos en el disco falla (con rollback automático de firmas/fotos en caso de error).

---

## 📂 Estructura del Proyecto

```
prueba_tecnica/
│
├── index.php                 # Vista principal (Dashboard con HTML5 semántico)
├── README.md                 # Guía de instalación y configuración
│
├── db/
│   ├── db.php                # Clase PDO de conexión e inicialización automática de BD
│   └── schema.sql            # Script SQL con estructura y datos semilla
│
├── api/
│   ├── get_guides.php        # Endpoint de consulta de guías y contadores (JSON)
│   ├── process_delivery.php  # Procesamiento de entregas (POST, JSON)
│   └── process_return.php    # Procesamiento de devoluciones (POST, JSON)
│
├── css/
│   └── style.css             # Estilos CSS premium (Diseño responsivo, Grid, Animaciones)
│
├── js/
│   └── app.js                # Lógica JS: AJAX, Canvas de Firma, Geolocalización y Toasts
│
└── uploads/                  # Directorio de evidencias físicas (se crea automáticamente)
    ├── signatures/           # Firmas almacenadas como archivos PNG
    └── evidence/             # Fotos de evidencia POD almacenadas con ID único seguro
```

---

## 🛠️ Requisitos del Sistema

*   **Servidor Web**: Apache / Nginx (ej. XAMPP, Laragon, WampServer).
*   **PHP**: Versión 8.0 o superior (con extensión `fileinfo` y `pdo_mysql` activadas).
*   **Base de datos**: MySQL / MariaDB.
*   **Navegador**: Cualquier navegador moderno con soporte para HTML5 Canvas y Geolocation API.

---

## 💻 Instalación y Configuración (¡Plug & Play!)

El sistema cuenta con un instalador automático integrado en la conexión de base de datos. Sigue estos simples pasos:

1.  **Clonar o copiar el proyecto** en la carpeta raíz de tu servidor local (ej. `htdocs/prueba_tecnica` o `www/prueba_tecnica`).
2.  **Configurar credenciales de MySQL** (Opcional):
    *   Abre el archivo [db/db.php](file:///c:/Users/Michael/Desktop/prueba_tecnica/db/db.php).
    *   Por defecto está configurado para:
        *   `DB_HOST`: `localhost`
        *   `DB_USER`: `root`
        *   `DB_PASS`: `""` (vacío)
        *   `DB_NAME`: `prueba_tecnica`
    *   Edita estos valores si tu entorno MySQL requiere usuario o contraseña diferentes.
3.  **Iniciar la aplicación**:
    *   Abre tu navegador y accede a `http://localhost/prueba_tecnica/`.
    *   **¡Eso es todo!** La primera vez que accedas a la aplicación, PHP detectará si la base de datos no existe y de manera automática:
        *   Creará la base de datos `prueba_tecnica`.
        *   Creará las tablas `guias` y `registros_guia` basándose en el script de base de datos.
        *   Insertará 7 registros de guías como datos de prueba.
        *   Creará la estructura de carpetas `uploads/signatures/` y `uploads/evidence/`.

> **Nota para Geolocalización**: Los navegadores modernos bloquean la API de geolocalización a menos que el sitio web se ejecute bajo un contexto seguro (`localhost` o `https://`). Al probar de manera local en `http://localhost/...` funcionará de forma nativa.

---

## 🛡️ Pruebas Recomendadas

1.  **Consulta de Filtros**: Selecciona un estado o una ciudad en el menú superior; verás cómo el listado y los contadores se actualizan al instante sin recargas.
2.  **Registro de Devolución**:
    *   Haz clic en "Devolver" en cualquier guía.
    *   Dibuja una firma en el recuadro, selecciona un archivo (foto de prueba) y el motivo.
    *   Envía el formulario y confirma que el estado de la guía cambia a `DEVUELTO` y el contador de devueltos se incrementa.
3.  **Entrega posterior de guía devuelta**:
    *   Busca la guía que acabas de devolver. Podrás ver que tiene habilitado el botón "Entregar".
    *   Registra la entrega con éxito y verifica que su estado final pasa a `ENTREGADO` correctamente.
