<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parafrase AI</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Auth Section (Hidden by default unless not logged in) -->
    <div id="auth-section" class="auth-container" style="display: none;">
        <div class="auth-card fade-in">
            <h2 class="logo" style="justify-content: center;"><i class="ri-quill-pen-line"></i> Parafrase</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Accede a tu asistente de escritura premium</p>
            
            <form id="login-form">
                <div class="input-group">
                    <label>Correo Electrónico</label>
                    <input type="email" id="login-email" class="auth-input" placeholder="tu@ejemplo.com" required>
                </div>
                <div class="input-group">
                    <label>Contraseña</label>
                    <input type="password" id="login-password" class="auth-input" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Iniciar Sesión</button>
                <p style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-muted);">
                    ¿No tienes cuenta? <a href="#" onclick="toggleAuth('register')" style="color: var(--primary);">Regístrate</a>
                </p>
            </form>

            <form id="register-form" style="display: none;">
                <div class="input-group">
                    <label>Usuario</label>
                    <input type="text" id="reg-username" class="auth-input" required>
                </div>
                <div class="input-group">
                    <label>Correo Electrónico</label>
                    <input type="email" id="reg-email" class="auth-input" required>
                </div>
                <div class="input-group">
                    <label>Contraseña</label>
                    <input type="password" id="reg-password" class="auth-input" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Cuenta</button>
                <p style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-muted);">
                    ¿Ya tienes cuenta? <a href="#" onclick="toggleAuth('login')" style="color: var(--primary);">Inicia Sesión</a>
                </p>
            </form>
        </div>
    </div>

    <!-- App Interface -->
    <nav class="sidebar" id="app-sidebar" style="display: none;">
        <div class="logo"><i class="ri-quill-pen-line"></i> Parafrase</div>
        
        <div class="nav-links">
            <a href="#" class="nav-link active" onclick="switchView('editor')">
                <i class="ri-edit-2-line"></i> Editor
            </a>
            <a href="#" class="nav-link" onclick="switchView('history')">
                <i class="ri-history-line"></i> Mi Biblioteca
            </a>
            <a href="#" class="nav-link">
                <i class="ri-settings-4-line"></i> Configuración
            </a>
        </div>

        <div style="margin-top: auto;">
             <a href="#" class="nav-link" onclick="logout()">
                <i class="ri-logout-box-line"></i> Salir
            </a>
        </div>
    </nav>

    <main class="main-content" id="app-main" style="display: none;">
        <header>
            <input type="text" id="project-title-input" class="title-input" value="Nuevo Proyecto" placeholder="Título del Proyecto">
            <div class="user-profile">
                <span id="user-display-name">Usuario</span>
                <div class="avatar">U</div>
            </div>
        </header>

        <!-- Editor View -->
        <div id="view-editor" class="fade-in">
            <div class="editor-container">
                <!-- Input Panel -->
                <div class="text-panel">
                    <div class="panel-header">
                        <span>Texto Original</span>
                        <div class="controls">
                            <span style="font-size: 0.8rem;">0 Palabras</span>
                        </div>
                    </div>
                    <div id="input-text" class="text-area" contenteditable="true" placeholder="Pega tu texto aquí para comenzar a reescribir..."></div>
                </div>

                <!-- Output Panel -->
                <div class="text-panel">
                    <div class="panel-header">
                        <span>Resultado Parafraseado</span>
                        <div class="controls">
                             <i class="ri-file-copy-line" style="cursor: pointer;" title="Copiar"></i>
                        </div>
                    </div>
                    <textarea id="output-text" class="text-area" placeholder="Tu texto reescrito aparecerá aquí..."></textarea>
                </div>
            </div>

            <div class="action-bar" style="margin-top: 2rem;">
                <button class="btn btn-secondary" onclick="clearText()">Limpiar</button>
                <button class="btn btn-primary" onclick="saveProject()">
                    <i class="ri-save-line"></i> Guardar
                </button>
            </div>
        </div>

        <!-- History View -->
        <div id="view-history" class="fade-in" style="display: none;">
            <div class="history-grid" id="history-container">
                <!-- Items injected via JS -->
            </div>
        </div>
    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>
