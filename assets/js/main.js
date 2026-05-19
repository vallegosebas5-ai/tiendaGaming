/* =====================================================
   Tienda Gaming — JavaScript Principal
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    initSearch();
    initQtyInputs();
    initAlertDismiss();
    initTooltips();
    initCategoryFilter();
});

// ── Buscador dinámico ────────────────────────────────
function initSearch() {
    const input = document.getElementById('searchInput');
    if (!input) return;

    input.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('[data-search]');
        let visible = 0;

        cards.forEach(card => {
            const text = card.getAttribute('data-search').toLowerCase();
            const match = !q || text.includes(q);
            card.closest('.col, [data-col]')?.style.setProperty('display', match ? '' : 'none');
            if (match) visible++;
        });

        const noResults = document.getElementById('noResults');
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    });
}

// ── Filtro por categoría ─────────────────────────────
function initCategoryFilter() {
    const btns = document.querySelectorAll('[data-filter-cat]');
    if (!btns.length) return;

    btns.forEach(btn => {
        btn.addEventListener('click', function () {
            btns.forEach(b => b.classList.remove('active', 'btn-gaming'));
            btns.forEach(b => b.classList.add('btn-outline-gaming'));
            this.classList.remove('btn-outline-gaming');
            this.classList.add('active', 'btn-gaming');

            const cat = this.getAttribute('data-filter-cat');
            const items = document.querySelectorAll('[data-cat]');
            let visible = 0;

            items.forEach(item => {
                const match = cat === 'all' || item.getAttribute('data-cat') === cat;
                item.closest('.col, [data-col]')?.style.setProperty('display', match ? '' : 'none');
                if (match) visible++;
            });

            const noResults = document.getElementById('noResults');
            if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
        });
    });
}

// ── Qty inputs +/- ──────────────────────────────────
function initQtyInputs() {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-qty]');
        if (!btn) return;
        const input = btn.closest('.qty-input')?.querySelector('input[name="cantidad"]');
        if (!input) return;

        let val = parseInt(input.value) || 1;
        const max = parseInt(input.getAttribute('max')) || 999;
        const action = btn.getAttribute('data-qty');

        if (action === 'plus')  val = Math.min(val + 1, max);
        if (action === 'minus') val = Math.max(val - 1, 1);
        input.value = val;
    });
}

// ── Auto-dismiss alerts ──────────────────────────────
function initAlertDismiss() {
    document.querySelectorAll('.alert-auto').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .6s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 600);
        }, 3500);
    });
}

// ── Bootstrap tooltips ───────────────────────────────
function initTooltips() {
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }
}

// ── Validación de formularios ────────────────────────
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let valid = true;
    form.querySelectorAll('[required]').forEach(field => {
        const wrapper = field.closest('.mb-3') || field.parentElement;
        const existing = wrapper.querySelector('.field-error');
        if (existing) existing.remove();

        if (!field.value.trim()) {
            valid = false;
            field.style.borderColor = '#ef4444';
            const err = document.createElement('div');
            err.className = 'field-error';
            err.style.cssText = 'color:#f87171;font-size:.8rem;margin-top:.25rem';
            err.textContent = 'Este campo es obligatorio.';
            wrapper.appendChild(err);
        } else {
            field.style.borderColor = '';
        }

        // Validar email
        if (field.type === 'email' && field.value.trim()) {
            const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailReg.test(field.value)) {
                valid = false;
                field.style.borderColor = '#ef4444';
                const err = document.createElement('div');
                err.className = 'field-error';
                err.style.cssText = 'color:#f87171;font-size:.8rem;margin-top:.25rem';
                err.textContent = 'Correo electrónico inválido.';
                (field.closest('.mb-3') || field.parentElement).appendChild(err);
            }
        }

        // Validar precio/número positivo
        if (field.type === 'number' && field.value.trim()) {
            if (parseFloat(field.value) < 0) {
                valid = false;
                field.style.borderColor = '#ef4444';
            }
        }
    });

    // Validar confirmación de contraseña
    const pass = form.querySelector('[name="contrasena"]');
    const confirm = form.querySelector('[name="confirmar"]');
    if (pass && confirm && pass.value && confirm.value && pass.value !== confirm.value) {
        valid = false;
        confirm.style.borderColor = '#ef4444';
        const wrapper = confirm.closest('.mb-3') || confirm.parentElement;
        const err = document.createElement('div');
        err.className = 'field-error';
        err.style.cssText = 'color:#f87171;font-size:.8rem;margin-top:.25rem';
        err.textContent = 'Las contraseñas no coinciden.';
        wrapper.appendChild(err);
    }

    return valid;
}

// ── Confirmar eliminación ────────────────────────────
function confirmDelete(msg) {
    return confirm(msg || '¿Estás seguro de que deseas eliminar este elemento?');
}

// ── Mostrar preview de imagen al subir ───────────────
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview || !input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

// ── Notificación toast ───────────────────────────────
function showToast(msg, type = 'success') {
    const container = document.getElementById('toastContainer') || (() => {
        const div = document.createElement('div');
        div.id = 'toastContainer';
        div.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem';
        document.body.appendChild(div);
        return div;
    })();

    const colors = { success: '#22c55e', danger: '#ef4444', info: '#06b6d4', warning: '#f59e0b' };
    const icons  = { success: '✓', danger: '✗', info: 'i', warning: '⚠' };

    const toast = document.createElement('div');
    toast.style.cssText = `
        background:#111128;border:1px solid ${colors[type] || colors.info};
        color:#f1f5f9;padding:.75rem 1.2rem;border-radius:10px;
        font-size:.9rem;box-shadow:0 4px 20px rgba(0,0,0,.4);
        display:flex;align-items:center;gap:.6rem;min-width:220px;
        animation:slideIn .25s ease;
    `;
    toast.innerHTML = `<span style="color:${colors[type]};font-weight:700">${icons[type]}</span> ${msg}`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .4s'; }, 3000);
    setTimeout(() => toast.remove(), 3500);
}

// CSS animation para toast
const style = document.createElement('style');
style.textContent = `@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}`;
document.head.appendChild(style);
