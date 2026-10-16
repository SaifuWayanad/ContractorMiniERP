/* Shared behaviour for every page. No framework; loaded after Bootstrap's bundle. */
(function () {
    'use strict';

    /* ---- Mobile sidebar ---- */
    const sidebarToggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebarToggle) {
        const setOpen = (open) => {
            document.body.classList.toggle('sidebar-open', open);
            sidebarToggle.setAttribute('aria-expanded', String(open));
        };
        sidebarToggle.addEventListener('click', () => setOpen(!document.body.classList.contains('sidebar-open')));
        backdrop?.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
    }

    /* ---- Loading state: disable the submit button and show "Saving…" to prevent double submits ---- */
    const setLoading = (form, submitter) => {
        const button = submitter || form.querySelector('[type="submit"]');
        form.dataset.submitting = 'true';
        form.querySelectorAll('[type="submit"]').forEach((b) => { b.disabled = true; });
        if (button && button.dataset.loadingText) {
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + button.dataset.loadingText;
        }
    };

    /* ---- Confirmation dialog for forms with data-confirm ---- */
    const modalEl = document.getElementById('confirmModal');
    const modal = modalEl && window.bootstrap ? new window.bootstrap.Modal(modalEl) : null;
    let pendingForm = null;
    let pendingSubmitter = null;

    modalEl?.querySelector('[data-confirm-accept]').addEventListener('click', () => {
        if (!pendingForm) return;
        pendingForm.dataset.confirmed = 'true';
        modal.hide();
        if (pendingSubmitter) pendingForm.requestSubmit(pendingSubmitter); else pendingForm.requestSubmit();
    });

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.dataset.submitting === 'true') {
            e.preventDefault();
            return;
        }

        if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
            e.preventDefault();
            if (!modal) {
                if (window.confirm(form.dataset.confirm)) { form.dataset.confirmed = 'true'; form.requestSubmit(e.submitter || undefined); }
                return;
            }
            pendingForm = form;
            pendingSubmitter = e.submitter || null;
            modalEl.querySelector('.modal-title').textContent = form.dataset.confirmTitle || 'Are you sure?';
            modalEl.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
            const accept = modalEl.querySelector('[data-confirm-accept]');
            accept.textContent = form.dataset.confirmButton || 'Confirm';
            const variant = form.dataset.confirmVariant || 'danger';
            accept.className = 'btn btn-' + variant + ' btn-sm px-4';
            const icon = modalEl.querySelector('[data-confirm-icon]');
            if (icon) {
                icon.className = 'confirm-modal-icon mb-3' + (variant !== 'danger' ? ' variant-' + variant : '');
                const iconMap = { danger: 'bi-trash3', warning: 'bi-exclamation-triangle', success: 'bi-check-circle', primary: 'bi-question-circle' };
                const i = icon.querySelector('i');
                if (i) i.className = 'bi ' + (iconMap[variant] || 'bi-trash3');
            }
            modal.show();
            return;
        }

        if (form.method.toLowerCase() === 'get') return;
        setLoading(form, e.submitter);
    });

    // Restore buttons if the page is shown again from the back/forward cache.
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-submitting]').forEach((f) => { delete f.dataset.submitting; delete f.dataset.confirmed; });
        document.querySelectorAll('[type="submit"][disabled]').forEach((b) => { b.disabled = false; });
    });

    /* ---- Filter bars: selects with data-autosubmit apply immediately; data-clears resets a dependent field ---- */
    document.querySelectorAll('[data-autosubmit]').forEach((el) => {
        el.addEventListener('change', () => {
            if (el.dataset.clears && el.form.elements[el.dataset.clears]) el.form.elements[el.dataset.clears].value = '';
            el.form.requestSubmit();
        });
    });

    /* ---- Show / hide password ---- */
    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.togglePassword);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    /* ---- Toasts disappear after a few seconds ---- */
    document.querySelectorAll('.toast[data-autohide]').forEach((el) => {
        setTimeout(() => el.classList.remove('show'), 5000);
    });

    /* ---- Indian state -> district suggestions (forms with a #districtData JSON block) ---- */
    const districtData = document.getElementById('districtData');
    if (districtData) {
        const districtsByState = JSON.parse(districtData.textContent);
        document.querySelectorAll('[data-state-input]').forEach((stateInput) => {
            const list = document.getElementById(stateInput.dataset.stateInput);
            const fill = () => {
                list.replaceChildren(...(districtsByState[stateInput.value] || []).map((d) => new Option(d)));
            };
            stateInput.addEventListener('input', fill);
            stateInput.addEventListener('change', fill);
            fill();
        });
    }
})();

/* ---- Checkbox that shows/hides (and enables/disables) a section: data-toggle-target="#id" ---- */
document.querySelectorAll('[data-toggle-target]').forEach((checkbox) => {
    const target = document.querySelector(checkbox.dataset.toggleTarget);
    if (!target) return;
    const sync = () => {
        target.hidden = !checkbox.checked;
        target.querySelectorAll('input, select, textarea').forEach((el) => { el.disabled = !checkbox.checked; });
    };
    checkbox.addEventListener('change', sync);
    sync();
});

/* ---- Row action menus inside scrollable tables float above the table instead of being clipped ---- */
/* On iOS Safari, overflow:auto clips pointer events for children even with position:fixed, so we
   move each menu to <body> while it is open and restore it on hidden. Per-toggle listeners ensure
   each menu is tracked independently so multi-row tables work correctly. */
if (window.bootstrap) {
    document.querySelectorAll('.table-responsive [data-bs-toggle="dropdown"]').forEach((toggle) => {
        const instance = window.bootstrap.Dropdown.getOrCreateInstance(toggle, { popperConfig: { strategy: 'fixed' } });

        toggle.addEventListener('show.bs.dropdown', function () {
            const menu = instance._menu;
            if (!menu || menu.parentElement === document.body) return;
            menu._returnTo = menu.parentElement;
            document.body.appendChild(menu);
        });

        toggle.addEventListener('hidden.bs.dropdown', function () {
            const menu = instance._menu;
            if (!menu || !menu._returnTo) return;
            menu._returnTo.appendChild(menu);
            delete menu._returnTo;
        });
    });
}
