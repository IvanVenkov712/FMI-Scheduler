document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alerts after 4s
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.opacity = '0';
            el.style.transition = 'opacity .4s';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

    // Confirm before delete
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Simple client-side required validation
    document.querySelectorAll('form.stack').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var ok = true;
            form.querySelectorAll('[required]').forEach(function (input) {
                input.style.borderColor = '';
                if (!input.value.trim()) {
                    input.style.borderColor = 'var(--danger)';
                    ok = false;
                }
            });
            if (!ok) {
                e.preventDefault();
                form.querySelector('[required]:invalid, [style*="danger"]')?.focus();
            }
        });
    });
});

// Simple fetch wrapper
var api = {
    request: function (method, url, body) {
        var opts = { method: method, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' };
        if (body) {
            if (body instanceof FormData) { opts.body = body; }
            else { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        }
        return fetch(url, opts).then(function (r) { return r.json(); });
    },
    get: function (url) { return this.request('GET', url); },
    post: function (url, body) { return this.request('POST', url, body); }
};
