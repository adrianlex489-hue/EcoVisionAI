document.addEventListener('DOMContentLoaded', function () {
    initSidebarToggle();
    initBSComponents();
    autoDismissAlerts();
    initScrollToTop();
    initDarkMode();
    initSubmenuToggles();
    initRippleEffect();
    initKeyboardShortcuts();
    initSidebarNavClose();
});

/* ===== SIDEBAR TOGGLE (PUSH MENU EN NAVBAR) ===== */
function initSidebarToggle() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    const navbar = document.querySelector('.navbar .container-fluid');

    if (navbar && !document.getElementById('sidebarToggle')) {
        const btn = document.createElement('button');
        btn.id = 'sidebarToggle';
        btn.className = 'sidebar-navbar-toggle';
        btn.setAttribute('aria-label', 'Menu');
        btn.innerHTML = '<i class="bi bi-list fs-4"></i>';
        navbar.insertBefore(btn, navbar.firstChild);
    }

    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('collapsed');
            handleSidebarOverlay();
        });
    }

    const closeBtn = document.getElementById('sidebarClose');
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.add('collapsed');
            handleSidebarOverlay();
        });
    }

    if (window.innerWidth >= 992) {
        sidebar.classList.remove('collapsed');
    } else {
        sidebar.classList.add('collapsed');
    }
}

function handleSidebarOverlay() {
    if (window.innerWidth > 991) return;

    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    let overlay = document.querySelector('.sidebar-overlay');

    if (!sidebar.classList.contains('collapsed')) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.addEventListener('click', function () {
                sidebar.classList.add('collapsed');
                handleSidebarOverlay();
            });
            document.body.appendChild(overlay);
            requestAnimationFrame(function () {
                overlay.classList.add('visible');
            });
        }
    } else if (overlay) {
        overlay.classList.remove('visible');
        setTimeout(function () {
            if (overlay.parentNode) overlay.remove();
        }, 300);
    }
}

function initSidebarNavClose() {
    document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 991) {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar && !sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                    handleSidebarOverlay();
                }
            }
        });
    });
}

/* ===== BOOTSTRAP COMPONENTS ===== */
function initBSComponents() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        try { new bootstrap.Tooltip(el); } catch (e) {}
    });
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        try { new bootstrap.Popover(el); } catch (e) {}
    });
}

/* ===== SUBMENU TOGGLES ===== */
function initSubmenuToggles() {
    document.querySelectorAll('.sidebar .nav-item.has-submenu > .nav-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const parent = this.closest('.nav-item');
            parent.classList.toggle('open');

            const submenu = parent.querySelector('.submenu');
            if (submenu) {
                if (parent.classList.contains('open')) {
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                } else {
                    submenu.style.maxHeight = '0';
                }
            }
        });
    });
}

/* ===== TOAST NOTIFICATION ===== */
function showToast(icon, title, message) {
    const container = document.querySelector('.toast-container');
    if (!container) return;

    const id = 'toast-' + Date.now();
    const html = '' +
        '<div id="' + id + '" class="toast" role="alert" aria-live="assertive" aria-visible="false">' +
        '  <div class="toast-header">' +
        '    <i class="' + icon + ' me-2"></i>' +
        '    <strong class="me-auto">' + title + '</strong>' +
        '    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>' +
        '  </div>' +
        '  <div class="toast-body">' + message + '</div>' +
        '</div>';

    container.insertAdjacentHTML('beforeend', html);

    const toastEl = document.getElementById(id);
    const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

/* ===== LOADING SPINNER ===== */
function showLoading(target) {
    let container;

    if (target) {
        if (typeof target === 'string') {
            container = document.querySelector(target);
        } else {
            container = target;
        }
    } else {
        container = document.body;
    }

    if (!container) return;

    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.style.cssText = '' +
        'position:absolute;top:0;left:0;width:100%;height:100%;' +
        'background:rgba(255,255,255,0.7);display:flex;align-items:center;' +
        'justify-content:center;z-index:1050;border-radius:inherit;';

    const spinner = document.createElement('div');
    spinner.className = 'spinner-border text-success';
    spinner.setAttribute('role', 'status');
    spinner.innerHTML = '<span class="visually-hidden">Cargando...</span>';

    overlay.appendChild(spinner);
    container.style.position = 'relative';
    container.appendChild(overlay);
}

function hideLoading(target) {
    let container;

    if (target) {
        if (typeof target === 'string') {
            container = document.querySelector(target);
        } else {
            container = target;
        }
    } else {
        container = document.body;
    }

    if (!container) return;

    const overlay = container.querySelector('.loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/* ===== AJAX ERROR HANDLER ===== */
function handleAjaxError(xhr, status, error) {
    let message = 'Ha ocurrido un error inesperado.';

    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
    } else if (xhr && xhr.statusText) {
        message = xhr.statusText;
    } else if (error) {
        message = error;
    }

    const container = document.querySelector('.toast-container');
    if (container) {
        showToast('fas fa-exclamation-circle text-danger', 'Error', message);
    } else {
        alert('Error: ' + message);
    }

    console.error('AJAX Error:', { xhr: xhr, status: status, error: error });
}

/* ===== AUTO-DISMISS ALERTS ===== */
function autoDismissAlerts() {
    document.querySelectorAll('.alert-dismissible.alert-auto-dismiss').forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/* ===== SCROLL TO TOP ===== */
function initScrollToTop() {
    // Button is rendered directly in footer.php — just wire the scroll event
    const btn = document.getElementById('scrollToTopBtn');
    if (!btn) return;

    window.addEventListener('scroll', function () {
        btn.style.opacity    = window.scrollY > 300 ? '1'       : '0';
        btn.style.visibility = window.scrollY > 300 ? 'visible' : 'hidden';
        btn.style.transform  = window.scrollY > 300 ? 'translateY(0)' : 'translateY(10px)';
    });

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

/* ===== TABLE RESPONSIVE HELPER ===== */
function makeTableResponsive(tableSelector) {
    const tables = document.querySelectorAll(tableSelector || '.table');
    tables.forEach(function (table) {
        if (!table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
}

/* ===== DARK MODE TOGGLE ===== */
function initDarkMode() {
    const saved = localStorage.getItem('ecovision-dark-mode');
    if (saved === 'true') {
        document.body.classList.add('dark-mode');
    }

    const toggle = document.getElementById('darkModeToggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('ecovision-dark-mode', isDark);

            const icon = toggle.querySelector('i');
            if (icon) {
                icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    }
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('ecovision-dark-mode', isDark);

    const toggle = document.getElementById('darkModeToggle');
    if (toggle) {
        const icon = toggle.querySelector('i');
        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
}

/* ===== KEYBOARD SHORTCUTS ===== */
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('[data-search]');
            if (searchInput) {
                searchInput.focus();
            }
        }

        if (e.key === 'Escape') {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar && window.innerWidth <= 991 && !sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                handleSidebarOverlay();
            }

            const modal = document.querySelector('.modal.show');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        }
    });
}

/* ===== CATEGORY COLOR MAPPING ===== */
function getCategoryColor(category) {
    const colors = {
        'plastico': '#0d6efd',
        'papel': '#198754',
        'vidrio': '#ffc107',
        'metal': '#dc3545',
        'organico': '#fd7e14',
        'plastic': '#0d6efd',
        'paper': '#198754',
        'glass': '#ffc107',
        'metal': '#dc3545',
        'organic': '#fd7e14'
    };

    return colors[category ? category.toLowerCase() : ''] || '#6c757d';
}

function getCategoryBadgeClass(category) {
    const classes = {
        'plastico': 'badge-plastico',
        'papel': 'badge-papel',
        'vidrio': 'badge-vidrio',
        'metal': 'badge-metal',
        'organico': 'badge-organico',
        'plastic': 'badge-plastico',
        'paper': 'badge-papel',
        'glass': 'badge-vidrio',
        'metal': 'badge-metal',
        'organic': 'badge-organico'
    };

    return classes[category ? category.toLowerCase() : ''] || 'bg-secondary';
}

function getCategoryBadgeBgClass(category) {
    const classes = {
        'plastico': 'badge-bg-plastico',
        'papel': 'badge-bg-papel',
        'vidrio': 'badge-bg-vidrio',
        'metal': 'badge-bg-metal',
        'organico': 'badge-bg-organico',
        'plastic': 'badge-bg-plastico',
        'paper': 'badge-bg-papel',
        'glass': 'badge-bg-vidrio',
        'metal': 'badge-bg-metal',
        'organic': 'badge-bg-organico'
    };

    return classes[category ? category.toLowerCase() : ''] || 'bg-secondary';
}

/* ===== FORMAT DATE ===== */
function formatDate(dateInput, options) {
    if (!dateInput) return '';

    let date;
    if (typeof dateInput === 'string' || typeof dateInput === 'number') {
        date = new Date(dateInput);
    } else if (dateInput instanceof Date) {
        date = dateInput;
    } else {
        return '';
    }

    if (isNaN(date.getTime())) return '';

    const defaultOpts = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };

    const merged = Object.assign({}, defaultOpts, options);

    return date.toLocaleDateString('es-ES', merged);
}

function formatDateShort(dateInput) {
    return formatDate(dateInput, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateFull(dateInput) {
    return formatDate(dateInput, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatTimeAgo(dateInput) {
    if (!dateInput) return '';

    let date;
    if (typeof dateInput === 'string' || typeof dateInput === 'number') {
        date = new Date(dateInput);
    } else if (dateInput instanceof Date) {
        date = dateInput;
    } else {
        return '';
    }

    if (isNaN(date.getTime())) return '';

    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 60) return 'justo ahora';
    if (diffMin < 60) return 'hace ' + diffMin + ' min';
    if (diffHour < 24) return 'hace ' + diffHour + ' h';
    if (diffDay < 7) return 'hace ' + diffDay + ' d';
    return formatDateShort(dateInput);
}

/* ===== RIPPLE EFFECT ===== */
function initRippleEffect() {
    document.querySelectorAll('.ripple').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const rect = btn.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            const ripple = document.createElement('span');
            ripple.className = 'ripple-effect';
            ripple.style.cssText = '' +
                'width:' + size + 'px;' +
                'height:' + size + 'px;' +
                'left:' + x + 'px;' +
                'top:' + y + 'px;';

            btn.appendChild(ripple);

            ripple.addEventListener('animationend', function () {
                ripple.remove();
            });
        });
    });
}
