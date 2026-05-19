/**
 * LogiTrack - Cliente JavaScript Vanilla
 * Gestión de Canvas para Firmas, Geolocalización, Validación y Fetch AJAX
 */

// --- Variables y Configuración Global ---
let currentLatitude = null;
let currentLongitude = null;
let signaturePads = {
    deliver: null,
    return: null
};

// Clase para controlar la firma en Canvas usando PointerEvents
class CanvasSignature {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        
        this.ctx = this.canvas.getContext('2d');
        this.isDrawing = false;
        this.hasSigned = false;
        this.points = [];

        this.init();
    }

    init() {
        this.resize();
        
        // Registrar eventos Pointer (unificados para mouse y touch)
        this.canvas.addEventListener('pointerdown', (e) => this.startDrawing(e));
        this.canvas.addEventListener('pointermove', (e) => this.draw(e));
        this.canvas.addEventListener('pointerup', () => this.stopDrawing());
        this.canvas.addEventListener('pointercancel', () => this.stopDrawing());
        
        // Prevenir comportamiento por defecto de gestos táctiles en el canvas
        this.canvas.style.touchAction = 'none';
    }

    resize() {
        const rect = this.canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        
        this.canvas.width = rect.width * ratio;
        this.canvas.height = rect.height * ratio;
        this.ctx.scale(ratio, ratio);
        
        this.resetContextStyles();
    }

    resetContextStyles() {
        this.ctx.strokeStyle = '#f3f4f6'; // Color de línea claro para fondo oscuro
        this.ctx.lineWidth = 2.5;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
    }

    getMousePos(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    startDrawing(e) {
        this.isDrawing = true;
        this.hasSigned = true; // El usuario comenzó a firmar
        this.points = [];
        const pos = this.getMousePos(e);
        this.points.push(pos);
        this.ctx.beginPath();
        this.ctx.moveTo(pos.x, pos.y);
    }

    draw(e) {
        if (!this.isDrawing) return;
        e.preventDefault();
        
        const pos = this.getMousePos(e);
        this.points.push(pos);

        if (this.points.length > 2) {
            this.ctx.clearRect(0, 0, this.canvas.clientWidth, this.canvas.clientHeight);
            this.ctx.beginPath();
            this.ctx.moveTo(this.points[0].x, this.points[0].y);
            
            let i;
            for (i = 1; i < this.points.length - 2; i++) {
                const xc = (this.points[i].x + this.points[i + 1].x) / 2;
                const yc = (this.points[i].y + this.points[i + 1].y) / 2;
                this.ctx.quadraticCurveTo(this.points[i].x, this.points[i].y, xc, yc);
            }
            this.ctx.quadraticCurveTo(
                this.points[i].x, 
                this.points[i].y, 
                this.points[i + 1].x, 
                this.points[i + 1].y
            );
            this.ctx.stroke();
        }
    }

    stopDrawing() {
        this.isDrawing = false;
        this.points = [];
    }

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.clientWidth, this.canvas.clientHeight);
        this.hasSigned = false;
        this.resetContextStyles();
    }

    exportBase64() {
        if (!this.hasSigned) return null;
        return this.canvas.toDataURL('image/png');
    }
}

// --- Inicialización al Cargar el Documento ---
document.addEventListener('DOMContentLoaded', () => {
    // Cargar guías inicialmente
    loadGuides();
    
    // Instanciar los Canvas de Firmas
    signaturePads.deliver = new CanvasSignature('canvas-signature-deliver');
    signaturePads.return = new CanvasSignature('canvas-signature-return');

    // Escuchadores de eventos para filtros
    document.getElementById('filter-estado').addEventListener('change', loadGuides);
    document.getElementById('filter-ciudad').addEventListener('change', loadGuides);
    document.getElementById('btn-clear').addEventListener('click', clearFilters);

    // Escuchadores para previsualizar imágenes POD
    setupFilePreview('deliver-file', 'deliver-file-preview');
    setupFilePreview('return-file', 'return-file-preview');

    // Control de envíos de formularios
    document.getElementById('form-deliver').addEventListener('submit', handleDeliverSubmit);
    document.getElementById('form-return').addEventListener('submit', handleReturnSubmit);
    
    // Redibujar canvas al redimensionar la ventana
    window.addEventListener('resize', () => {
        if (signaturePads.deliver) signaturePads.deliver.resize();
        if (signaturePads.return) signaturePads.return.resize();
    });
});

// --- Funciones de Modales ---
function openModal(modalId, guiaId) {
    const overlay = document.getElementById(modalId);
    if (!overlay) return;

    // Resetear formulario
    const form = overlay.querySelector('form');
    if (form) {
        form.reset();
        // Limpiar previsualizaciones de archivos
        const preview = form.querySelector('.file-preview');
        if (preview) {
            preview.innerHTML = '';
            preview.style.display = 'none';
        }
    }

    // Configurar ID de guía
    if (modalId === 'modal-deliver') {
        document.getElementById('deliver-guia-id').value = guiaId;
        if (signaturePads.deliver) signaturePads.deliver.clear();
        requestGeolocation('deliver');
    } else if (modalId === 'modal-return') {
        document.getElementById('return-guia-id').value = guiaId;
        if (signaturePads.return) signaturePads.return.clear();
        requestGeolocation('return');
    }

    // Mostrar modal con transición CSS
    overlay.classList.add('active');
}

function closeModal(modalId) {
    const overlay = document.getElementById(modalId);
    if (overlay) {
        overlay.classList.remove('active');
    }
}

// --- Funciones de Firma ---
function clearCanvas(type) {
    if (signaturePads[type]) {
        signaturePads[type].clear();
    }
}

// --- Geolocalización ---
function requestGeolocation(type) {
    const dot = document.getElementById(`${type}-geo-dot`);
    const text = document.getElementById(`${type}-geo-text`);

    currentLatitude = null;
    currentLongitude = null;

    if (dot && text) {
        dot.className = 'geo-dot pending';
        text.innerHTML = '<i class="fa-solid fa-location-crosshairs fa-spin"></i> Obteniendo coordenadas GPS...';
    }

    if (!navigator.geolocation) {
        updateGeoStatus(type, false, 'Geolocalización no soportada por el navegador.');
        return;
    }

    const options = {
        enableHighAccuracy: true,
        timeout: 8000,
        maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
        (position) => {
            currentLatitude = position.coords.latitude;
            currentLongitude = position.coords.longitude;
            updateGeoStatus(type, true, `Ubicación capturada (${currentLatitude.toFixed(6)}, ${currentLongitude.toFixed(6)})`);
        },
        (error) => {
            let msg = 'Error de GPS.';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg = 'Permiso denegado para ubicación GPS.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = 'Señal de GPS no disponible.';
                    break;
                case error.TIMEOUT:
                    msg = 'Tiempo agotado al obtener ubicación.';
                    break;
            }
            updateGeoStatus(type, false, `${msg} (Se registrará sin coordenadas)`);
        },
        options
    );
}

function updateGeoStatus(type, isSuccess, message) {
    const dot = document.getElementById(`${type}-geo-dot`);
    const text = document.getElementById(`${type}-geo-text`);

    if (dot && text) {
        dot.className = isSuccess ? 'geo-dot success' : 'geo-dot error';
        text.innerHTML = isSuccess 
            ? `<i class="fa-solid fa-location-dot" style="color: var(--color-delivered)"></i> ${message}`
            : `<i class="fa-solid fa-triangle-exclamation" style="color: var(--color-danger)"></i> ${message}`;
    }
}

// --- Previsualización de Fotos ---
function setupFilePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!input || !preview) return;

    input.addEventListener('change', () => {
        preview.innerHTML = '';
        preview.style.display = 'none';

        const file = input.files[0];
        if (!file) return;

        // Validación frontend del tipo de archivo
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            showToast('Formato de archivo no válido. Solo JPG y PNG.', 'error');
            input.value = '';
            return;
        }

        // Validación frontend del tamaño de archivo (Max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showToast('El archivo supera el límite de 5MB.', 'error');
            input.value = '';
            return;
        }

        // Mostrar previsualización
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
}

// --- Carga AJAX de Datos (Listado y Contadores) ---
async function loadGuides() {
    const estado = document.getElementById('filter-estado').value;
    const ciudad = document.getElementById('filter-ciudad').value;
    const tbody = document.getElementById('guides-table-body');
    const selectCiudad = document.getElementById('filter-ciudad');

    try {
        const queryParams = new URLSearchParams({ estado, ciudad }).toString();
        const response = await fetch(`api/get_guides.php?${queryParams}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="7" class="empty-state"><p class="text-danger">${data.message}</p></td></tr>`;
            return;
        }

        // 1. Renderizar la tabla de guías
        if (data.guides.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-state">
                        <div class="empty-state-icon"><i class="fa-solid fa-box-open"></i></div>
                        <p>No se encontraron guías con los filtros seleccionados.</p>
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = data.guides.map(guide => {
                const dateFormatted = new Date(guide.fecha_creacion).toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Lógica de botones según el estado
                let actionBtns = '';
                if (guide.estado === 'PENDIENTE') {
                    actionBtns = `
                        <button class="btn-action btn-deliver" onclick="openModal('modal-deliver', ${guide.id})">
                            <i class="fa-solid fa-box-open"></i> Entregar
                        </button>
                        <button class="btn-action btn-return" onclick="openModal('modal-return', ${guide.id})">
                            <i class="fa-solid fa-circle-xmark"></i> Devolver
                        </button>
                    `;
                } else if (guide.estado === 'DEVUELTO') {
                    // "Poder ser entregada posteriormente"
                    actionBtns = `
                        <button class="btn-action btn-deliver" onclick="openModal('modal-deliver', ${guide.id})">
                            <i class="fa-solid fa-box-open"></i> Entregar
                        </button>
                        <span style="color: var(--text-muted); font-size: 0.8rem; padding-right: 0.5rem;">Devuelto</span>
                    `;
                } else {
                    actionBtns = `<span style="color: var(--color-delivered); font-size: 0.85rem; font-weight: 500;"><i class="fa-solid fa-check-double"></i> Finalizado</span>`;
                }

                return `
                    <tr>
                        <td data-label="N° Guía" style="font-weight: 600; color: var(--color-accent);">${escapeHTML(guide.numero_guia)}</td>
                        <td data-label="Cliente" style="font-weight: 500;">${escapeHTML(guide.cliente)}</td>
                        <td data-label="Destino">${escapeHTML(guide.ciudad_destino)}</td>
                        <td data-label="Dirección">${escapeHTML(guide.direccion)}</td>
                        <td data-label="Estado">
                            <span class="badge badge-${guide.estado.toLowerCase()}">
                                <span class="status-dot" style="background-color: var(--color-${guide.estado.toLowerCase()}); box-shadow: none; animation: none; width: 6px; height: 6px;"></span>
                                ${guide.estado}
                            </span>
                        </td>
                        <td data-label="Creación" style="font-size: 0.85rem; color: var(--text-secondary);">${dateFormatted}</td>
                        <td data-label="Acciones" style="text-align: right;">
                            <div class="action-buttons">${actionBtns}</div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // 2. Actualizar Contadores del Dashboard
        document.getElementById('count-pending').textContent = data.counters.pending;
        document.getElementById('count-delivered').textContent = data.counters.delivered;
        document.getElementById('count-returned').textContent = data.counters.returned;

        // 3. Rellenar dinámicamente el selector de ciudades para filtros si no se ha filtrado
        const currentSelectedCity = selectCiudad.value;
        selectCiudad.innerHTML = '<option value="">Todas las ciudades</option>';
        data.cities.forEach(city => {
            const opt = document.createElement('option');
            opt.value = city;
            opt.textContent = city;
            if (city === currentSelectedCity) {
                opt.selected = true;
            }
            selectCiudad.appendChild(opt);
        });

    } catch (error) {
        console.error("Error cargando guías:", error);
        showToast('Error de comunicación con el servidor al cargar guías.', 'error');
    }
}

function clearFilters() {
    document.getElementById('filter-estado').value = '';
    document.getElementById('filter-ciudad').value = '';
    loadGuides();
}

// --- Procesamiento de Envíos AJAX (Formularios) ---

// Auxiliar para convertir Base64 a un archivo Blob binario
function base64ToBlob(base64, mimeType = 'image/png') {
    const parts = base64.split(',');
    const base64Data = parts.length > 1 ? parts[1] : parts[0];
    const byteString = atob(base64Data);
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    
    for (let i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }
    return new Blob([ab], { type: mimeType });
}

// Envío de Formulario: Entrega
async function handleDeliverSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btnSubmit = document.getElementById('btn-submit-deliver');

    // --- Validaciones Frontend ---
    const recibe = document.getElementById('deliver-recibe').value.trim();
    if (recibe.length < 3) {
        showToast('El nombre de quien recibe es obligatorio y debe tener al menos 3 caracteres.', 'error');
        return;
    }

    const fileInput = document.getElementById('deliver-file');
    if (!fileInput.files || fileInput.files.length === 0) {
        showToast('Debe subir la foto POD como prueba de entrega.', 'error');
        return;
    }

    const base64Sig = signaturePads.deliver.exportBase64();
    if (!base64Sig) {
        showToast('La firma digital del receptor es obligatoria.', 'error');
        return;
    }

    // Desactivar botón para evitar envíos múltiples
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';

    try {
        const formData = new FormData(form);
        
        // Agregar la firma digital codificada como Blob
        const sigBlob = base64ToBlob(base64Sig, 'image/png');
        formData.append('signature', sigBlob, 'firma_entrega.png');

        // Agregar coordenadas GPS si están disponibles
        if (currentLatitude !== null && currentLongitude !== null) {
            formData.append('latitude', currentLatitude);
            formData.append('longitude', currentLongitude);
        }

        const response = await fetch('api/process_delivery.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeModal('modal-deliver');
            loadGuides();
        } else {
            showToast(data.message, 'error');
        }

    } catch (err) {
        console.error("Error al registrar entrega:", err);
        showToast('Error en la conexión al enviar la entrega.', 'error');
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Registrar Entrega';
    }
}

// Envío de Formulario: Devolución
async function handleReturnSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btnSubmit = document.getElementById('btn-submit-return');

    // --- Validaciones Frontend ---
    const reason = document.getElementById('return-reason').value;
    if (!reason) {
        showToast('Debe seleccionar un motivo de devolución.', 'error');
        return;
    }

    const fileInput = document.getElementById('return-file');
    if (!fileInput.files || fileInput.files.length === 0) {
        showToast('Debe subir la foto POD como evidencia de devolución.', 'error');
        return;
    }

    const base64Sig = signaturePads.return.exportBase64();
    if (!base64Sig) {
        showToast('La firma digital del transportador es obligatoria.', 'error');
        return;
    }

    // Desactivar botón para evitar envíos múltiples
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';

    try {
        const formData = new FormData(form);
        
        // Agregar la firma digital codificada como Blob
        const sigBlob = base64ToBlob(base64Sig, 'image/png');
        formData.append('signature', sigBlob, 'firma_devolucion.png');

        // Agregar coordenadas GPS si están disponibles
        if (currentLatitude !== null && currentLongitude !== null) {
            formData.append('latitude', currentLatitude);
            formData.append('longitude', currentLongitude);
        }

        const response = await fetch('api/process_return.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeModal('modal-return');
            loadGuides();
        } else {
            showToast(data.message, 'error');
        }

    } catch (err) {
        console.error("Error al registrar devolución:", err);
        showToast('Error en la conexión al enviar la devolución.', 'error');
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Registrar Devolución';
    }
}

// --- Utilidades del Sistema ---

// Mostrar Notificaciones Flotantes (Toasts)
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = '<i class="fa-solid fa-circle-check" style="color: var(--color-delivered)"></i>';
    if (type === 'error') {
        icon = '<i class="fa-solid fa-circle-exclamation" style="color: var(--color-danger)"></i>';
    } else if (type === 'info') {
        icon = '<i class="fa-solid fa-info-circle" style="color: var(--color-pending)"></i>';
    }

    toast.innerHTML = `
        ${icon}
        <span class="toast-message">${message}</span>
    `;

    container.appendChild(toast);

    // Activar animación de entrada
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Animación de salida y remoción
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// Escapar HTML para evitar inyecciones XSS
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}
