var App = window.App || {};

App.validation = (function () {
    'use strict';

    var rules = {
        required: function (value) {
            return value !== null && value !== undefined && String(value).trim() !== '';
        },
        email: function (value) {
            if (!value) return true;
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value));
        },
        numeric: function (value) {
            if (!value && value !== 0) return true;
            return !isNaN(parseFloat(value)) && isFinite(value);
        },
        min: function (value, min) {
            if (!value && value !== 0) return true;
            return parseFloat(value) >= parseFloat(min);
        },
        max: function (value, max) {
            if (!value && value !== 0) return true;
            return parseFloat(value) <= parseFloat(max);
        },
        minLength: function (value, len) {
            if (!value) return true;
            return String(value).length >= parseInt(len, 10);
        },
        dateBefore: function (value, otherFieldName, form) {
            if (!value) return true;
            var otherField = form.querySelector('[name="' + otherFieldName + '"]');
            if (!otherField || !otherField.value) return true;
            return new Date(value) < new Date(otherField.value);
        },
        dateAfter: function (value, otherFieldName, form) {
            if (!value) return true;
            var otherField = form.querySelector('[name="' + otherFieldName + '"]');
            if (!otherField || !otherField.value) return true;
            return new Date(value) > new Date(otherField.value);
        }
    };

    var messages = {
        required: 'This field is required.',
        email: 'Please enter a valid email address.',
        numeric: 'Please enter a number.',
        min: 'Value must be at least {0}.',
        max: 'Value must be at most {0}.',
        minLength: 'Must be at least {0} characters.',
        dateBefore: 'Must be before the end date.',
        dateAfter: 'Must be after the start date.'
    };

    function getMessage(rule, params) {
        var msg = messages[rule] || 'Invalid value.';
        if (params) {
            for (var i = 0; i < params.length; i++) {
                msg = msg.replace('{' + i + '}', params[i]);
            }
        }
        return msg;
    }

    function showError(field, message) {
        var container = field.closest('.field') || field.parentElement;
        if (container) container.classList.add('has-error');

        var existing = container ? container.querySelector('.field-error.js-error') : null;
        if (existing) {
            existing.textContent = message;
            return;
        }

        var errorEl = document.createElement('div');
        errorEl.className = 'field-error js-error';
        errorEl.textContent = message;

        if (container) {
            container.appendChild(errorEl);
        } else {
            field.parentNode.insertBefore(errorEl, field.nextSibling);
        }
    }

    function clearError(field) {
        var container = field.closest('.field') || field.parentElement;
        if (container) container.classList.remove('has-error');

        var errors = (container || field.parentNode).querySelectorAll('.field-error.js-error');
        for (var i = 0; i < errors.length; i++) {
            errors[i].remove();
        }
    }

    function clearAllErrors(form) {
        var errors = form.querySelectorAll('.field-error.js-error');
        for (var i = 0; i < errors.length; i++) errors[i].remove();
        var fields = form.querySelectorAll('.has-error');
        for (var j = 0; j < fields.length; j++) fields[j].classList.remove('has-error');
    }

    function validateField(field, form) {
        var validations = (field.getAttribute('data-validate') || '').split('|').filter(Boolean);
        clearError(field);

        for (var i = 0; i < validations.length; i++) {
            var parts = validations[i].split(':');
            var ruleName = parts[0];
            var params = parts.slice(1);
            var ruleFn = rules[ruleName];

            if (!ruleFn) continue;

            var args = [field.value].concat(params);
            if (ruleName === 'dateBefore' || ruleName === 'dateAfter') {
                args.push(form);
            }

            if (!ruleFn.apply(null, args)) {
                showError(field, getMessage(ruleName, params));
                return false;
            }
        }
        return true;
    }

    function validateForm(form) {
        clearAllErrors(form);
        var fields = form.querySelectorAll('[data-validate]');
        var valid = true;

        for (var i = 0; i < fields.length; i++) {
            if (!validateField(fields[i], form)) {
                valid = false;
            }
        }
        return valid;
    }

    function init() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            if (!form.querySelector('[data-validate]')) return;

            if (!validateForm(form)) {
                e.preventDefault();
                var firstError = form.querySelector('.has-error');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        document.addEventListener('blur', function (e) {
            var field = e.target;
            if (!field || !field.getAttribute('data-validate')) return;
            var form = field.closest('form');
            if (form) validateField(field, form);
        }, true);
    }

    return { init: init, validateForm: validateForm, validateField: validateField, rules: rules };
})();

document.addEventListener('DOMContentLoaded', function () {
    App.validation.init();
});
