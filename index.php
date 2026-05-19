<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Guías de Transporte | Logística Premium</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Iconos FontAwesome para mejorar la visualización -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="app-container">
        <!-- Encabezado principal -->
        <header>
            <div class="logo-section">
                <h1>LogiTrack</h1>
                <p>Gestión de entregas de guías en tiempo real</p>
            </div>
            <div class="status-badge-server">
                <span class="status-dot"></span>
                <span>Servidor en Línea (AJAX)</span>
            </div>
        </header>

        <!-- Contadores / Estadísticas rápidas -->
        <section class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-info">
                    <h3>Pendientes</h3>
                    <div class="stat-number" id="count-pending">0</div>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="stat-card delivered">
                <div class="stat-info">
                    <h3>Entregadas</h3>
                    <div class="stat-number" id="count-delivered">0</div>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="stat-card returned">
                <div class="stat-info">
                    <h3>Devueltas</h3>
                    <div class="stat-number" id="count-returned">0</div>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
            </div>
        </section>

        <!-- Filtros de búsqueda -->
        <section class="filter-section">
            <div class="filter-group">
                <label for="filter-estado">Filtrar por Estado</label>
                <select id="filter-estado" class="select-custom">
                    <option value="">Todos los estados</option>
                    <option value="PENDIENTE">PENDIENTE</option>
                    <option value="ENTREGADO">ENTREGADO</option>
                    <option value="DEVUELTO">DEVUELTO</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-ciudad">Filtrar por Ciudad Destino</label>
                <select id="filter-ciudad" class="select-custom">
                    <option value="">Todas las ciudades</option>
                </select>
            </div>
            <button id="btn-clear" class="btn-clear-filters">
                <i class="fa-solid fa-filter-circle-xmark" style="margin-right: 0.5rem;"></i>
                Limpiar Filtros
            </button>
        </section>

        <!-- Tabla principal de guías -->
        <section class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>N° Guía</th>
                        <th>Cliente</th>
                        <th>Destino</th>
                        <th>Dirección</th>
                        <th>Estado</th>
                        <th>Creación</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="guides-table-body">
                    <!-- Se carga dinámicamente mediante AJAX -->
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                            </div>
                            <p>Cargando guías de transporte...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>

    <!-- MODAL 1: ENTREGA DE GUÍA -->
    <div id="modal-deliver" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fa-solid fa-box-open" style="color: var(--color-delivered); margin-right: 0.5rem;"></i> Registrar Entrega</h2>
                <button class="btn-close-modal" onclick="closeModal('modal-deliver')">&times;</button>
            </div>
            <form id="form-deliver" novalidate>
                <input type="hidden" name="guia_id" id="deliver-guia-id">
                <div class="modal-body">
                    
                    <div class="form-group">
                        <label for="deliver-recibe">Nombre de quien recibe <span class="required-star">*</span></label>
                        <input type="text" id="deliver-recibe" name="nombre_recibe" class="input-custom" placeholder="Ej. Juan Pérez (Mínimo 3 letras)" required>
                    </div>

                    <div class="form-group">
                        <label for="deliver-obs">Observaciones</label>
                        <textarea id="deliver-obs" name="observacion" class="input-custom" placeholder="Detalles de la entrega (opcional)"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Foto de prueba de entrega (POD) <span class="required-star">*</span></label>
                        <div class="file-upload-wrapper">
                            <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                            <div class="upload-text">Arrastra o <span>selecciona una foto</span> (Max 5MB, JPG/PNG)</div>
                            <input type="file" id="deliver-file" name="pod_image" accept="image/png, image/jpeg, image/jpg" required>
                            <div class="file-preview" id="deliver-file-preview"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Firma digital del receptor <span class="required-star">*</span></label>
                        <div class="signature-wrapper">
                            <canvas id="canvas-signature-deliver" class="signature-canvas"></canvas>
                            <div class="signature-actions">
                                <button type="button" class="btn-clear-canvas" onclick="clearCanvas('deliver')">Limpiar Firma</button>
                            </div>
                        </div>
                    </div>

                    <!-- Estado del GPS -->
                    <div class="form-group">
                        <div class="geo-status">
                            <span class="geo-dot pending" id="deliver-geo-dot"></span>
                            <span id="deliver-geo-text"><i class="fa-solid fa-location-crosshairs fa-spin"></i> Solicitando ubicación GPS...</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('modal-deliver')">Cancelar</button>
                    <button type="submit" class="btn-modal btn-submit" id="btn-submit-deliver">Registrar Entrega</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: DEVOLUCIÓN DE GUÍA -->
    <div id="modal-return" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fa-solid fa-circle-xmark" style="color: var(--color-returned); margin-right: 0.5rem;"></i> Registrar Devolución</h2>
                <button class="btn-close-modal" onclick="closeModal('modal-return')">&times;</button>
            </div>
            <form id="form-return" novalidate>
                <input type="hidden" name="guia_id" id="return-guia-id">
                <div class="modal-body">
                    
                    <div class="form-group">
                        <label for="return-reason">Motivo de Devolución <span class="required-star">*</span></label>
                        <select id="return-reason" name="motivo_devolucion" class="select-custom" required>
                            <option value="">Seleccione un motivo...</option>
                            <option value="Dirección incorrecta / incompleta">Dirección incorrecta / incompleta</option>
                            <option value="Cliente ausente en dirección de destino">Cliente ausente en dirección de destino</option>
                            <option value="Rechazado por el cliente">Rechazado por el cliente</option>
                            <option value="Mercancía con daños o averiada">Mercancía con daños o averiada</option>
                            <option value="Zona roja / de difícil acceso sin seguridad">Zona roja / de difícil acceso sin seguridad</option>
                            <option value="Otro motivo (especificar en observaciones)">Otro motivo (especificar en observaciones)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="return-obs">Observaciones</label>
                        <textarea id="return-obs" name="observacion" class="input-custom" placeholder="Explicación detallada del motivo (opcional)"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Foto evidencia de la devolución <span class="required-star">*</span></label>
                        <div class="file-upload-wrapper">
                            <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                            <div class="upload-text">Arrastra o <span>selecciona una foto</span> (Max 5MB, JPG/PNG)</div>
                            <input type="file" id="return-file" name="pod_image" accept="image/png, image/jpeg, image/jpg" required>
                            <div class="file-preview" id="return-file-preview"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Firma digital del transportador (Evidencia) <span class="required-star">*</span></label>
                        <div class="signature-wrapper">
                            <canvas id="canvas-signature-return" class="signature-canvas"></canvas>
                            <div class="signature-actions">
                                <button type="button" class="btn-clear-canvas" onclick="clearCanvas('return')">Limpiar Firma</button>
                            </div>
                        </div>
                    </div>

                    <!-- Estado del GPS -->
                    <div class="form-group">
                        <div class="geo-status">
                            <span class="geo-dot pending" id="return-geo-dot"></span>
                            <span id="return-geo-text"><i class="fa-solid fa-location-crosshairs fa-spin"></i> Solicitando ubicación GPS...</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('modal-return')">Cancelar</button>
                    <button type="submit" class="btn-modal btn-submit" id="btn-submit-return">Registrar Devolución</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contenedor de notificaciones flotantes (Toasts) -->
    <div id="toast-container" class="toast-container"></div>

    <script src="js/app.js"></script>
</body>
</html>
