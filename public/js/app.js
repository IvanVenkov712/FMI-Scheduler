document.addEventListener('DOMContentLoaded', function () {
    var hasErrorFlash = document.querySelector('.alert.err') !== null;

    function formStorageKey(form) {
        var action = form.getAttribute('action') || window.location.pathname;
        var method = (form.getAttribute('method') || 'get').toLowerCase();

        return 'form-draft:' + method + ':' + action;
    }

    function isSensitiveField(input) {
        var name = (input.name || '').toLowerCase();
        var type = (input.type || '').toLowerCase();

        return type === 'password'
            || type === 'file'
            || name.includes('password')
            || name.includes('token')
            || name.includes('secret')
            || name.includes('access_key');
    }

    function saveFormDraft(form) {
        var data = {};
        form.querySelectorAll('input, select, textarea').forEach(function (input) {
            if (!input.name || isSensitiveField(input)) return;
            if ((input.type === 'checkbox' || input.type === 'radio') && !input.checked) return;
            data[input.name] = input.value;
        });

        try {
            sessionStorage.setItem(formStorageKey(form), JSON.stringify(data));
        } catch (e) {}
    }

    function restoreFormDraft(form) {
        var raw;
        try {
            raw = sessionStorage.getItem(formStorageKey(form));
        } catch (e) {
            return;
        }
        if (!raw) return;

        var data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            return;
        }

        form.querySelectorAll('input, select, textarea').forEach(function (input) {
            if (!input.name || isSensitiveField(input) || !Object.prototype.hasOwnProperty.call(data, input.name)) return;
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = input.value === data[input.name];
                return;
            }
            input.value = data[input.name];
        });
    }

    function clearFormDraft(form) {
        try {
            sessionStorage.removeItem(formStorageKey(form));
        } catch (e) {}
    }

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

    function setCustomError(input, message) {
        if (input) input.setCustomValidity(message || '');
    }

    function clearFieldError(input) {
        if (!input) return;
        input.classList.remove('is-invalid');
        input.setAttribute('aria-invalid', 'false');
        var error = input.closest('.field')?.querySelector('.field-error');
        if (error) error.textContent = '';
    }

    function syncRelatedValidation(form) {
        form.querySelectorAll('input[type="text"][required], textarea[required]').forEach(function (input) {
            setCustomError(input, input.value && !input.value.trim() ? 'This field cannot contain only spaces.' : '');
        });

        var start = form.querySelector('[name="start_time"], [name="start_date"]');
        var end = form.querySelector('[name="end_time"], [name="end_date"]');
        setCustomError(end, '');
        if (start && end && start.value && end.value && end.value <= start.value) {
            setCustomError(end, 'The end value must be after the start value.');
        }

        var fromRoom = form.querySelector('[name="from_room_id"]');
        var toRoom = form.querySelector('[name="to_room_id"]');
        setCustomError(toRoom, '');
        if (fromRoom && toRoom && fromRoom.value && fromRoom.value === toRoom.value) {
            setCustomError(toRoom, 'Choose a different destination room.');
        }

        var deliveryMode = form.querySelector('[name="delivery_mode"]');
        var room = form.querySelector('[name="room_id"]');
        var platform = form.querySelector('[name="online_access[platform]"]');
        var meetingLink = form.querySelector('[name="online_access[meeting_link]"]');
        if (deliveryMode) {
            var needsRoom = deliveryMode.value === 'physical' || deliveryMode.value === 'hybrid';
            var needsOnlineAccess = deliveryMode.value === 'online' || deliveryMode.value === 'hybrid';
            if (room) {
                room.required = needsRoom;
                room.disabled = deliveryMode.value === 'online';
                if (room.disabled) clearFieldError(room);
            }
            form.querySelectorAll('[name^="online_access["]').forEach(function (input) {
                if (!input) return;
                input.required = needsOnlineAccess && (input === platform || input === meetingLink);
                input.disabled = !needsOnlineAccess;
                if (input.disabled) clearFieldError(input);
            });
        }

        if (form.matches('[action="/import-export/import-xml"]')) {
            var xmlFile = form.querySelector('[name="file"]');
            var xmlText = form.querySelector('[name="xml"]');
            setCustomError(xmlText, '');
            if (xmlFile && xmlText && !xmlFile.value && !xmlText.value.trim()) {
                setCustomError(xmlText, 'Choose an XML file or paste XML content.');
            }
        }
    }

    function refreshFieldState(input) {
        var field = input.closest('.field');
        if (!field) return;

        var error = field.querySelector('.field-error');
        if (!error) {
            error = document.createElement('small');
            error.className = 'field-error';
            error.setAttribute('aria-live', 'polite');
            field.appendChild(error);
        }

        var showError = input.dataset.touched === 'true' && !input.validity.valid;
        input.classList.toggle('is-invalid', showError);
        input.setAttribute('aria-invalid', showError ? 'true' : 'false');
        error.textContent = showError ? input.validationMessage : '';
    }

    function refreshTouchedFields(form) {
        form.querySelectorAll('[data-touched="true"]').forEach(refreshFieldState);
    }

    document.querySelectorAll('form').forEach(function (form) {
        var controls = Array.from(form.querySelectorAll('input, select, textarea'));
        if (!controls.length) return;

        if (hasErrorFlash) {
            restoreFormDraft(form);
        } else {
            clearFormDraft(form);
        }
        syncRelatedValidation(form);
        controls.forEach(function (input) {
            input.addEventListener('blur', function () {
                input.dataset.touched = 'true';
                syncRelatedValidation(form);
                refreshTouchedFields(form);
            });
            input.addEventListener('input', function () {
                syncRelatedValidation(form);
                refreshTouchedFields(form);
            });
            input.addEventListener('change', function () {
                syncRelatedValidation(form);
                refreshTouchedFields(form);
            });
        });

        form.addEventListener('submit', function (e) {
            syncRelatedValidation(form);
            controls.forEach(function (input) {
                input.dataset.touched = 'true';
                refreshFieldState(input);
            });
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                form.querySelector(':invalid')?.focus();
                return;
            }
            saveFormDraft(form);
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
