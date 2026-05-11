var App = window.App || {};

(function () {
    'use strict';

    function initSidebarToggle() {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        var toggle = document.createElement('button');
        toggle.className = 'sidebar-toggle';
        toggle.setAttribute('aria-label', 'Toggle navigation');
        toggle.innerHTML = '&#9776;';

        var backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';

        document.body.appendChild(toggle);
        document.body.appendChild(backdrop);

        function open() {
            sidebar.classList.add('sidebar-open');
            backdrop.classList.add('active');
        }

        function close() {
            sidebar.classList.remove('sidebar-open');
            backdrop.classList.remove('active');
        }

        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('sidebar-open')) {
                close();
            } else {
                open();
            }
        });

        backdrop.addEventListener('click', close);
    }

    function initAlertDismiss() {
        var alerts = document.querySelectorAll('.alert-success, .alert-danger');
        for (var i = 0; i < alerts.length; i++) {
            (function (alert) {
                setTimeout(function () {
                    alert.classList.add('alert-dismissing');
                    setTimeout(function () {
                        if (alert.parentNode) alert.parentNode.removeChild(alert);
                    }, 400);
                }, 4000);
            })(alerts[i]);
        }
    }

    function initDeleteConfirmations() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('button[onclick*="confirm"]');
            if (!btn) return;

            var msg = btn.getAttribute('onclick');
            if (!msg) return;

            var match = msg.match(/confirm\(['"](.+?)['"]\)/);
            if (!match) return;

            e.preventDefault();
            e.stopPropagation();

            var overlay = document.createElement('div');
            overlay.className = 'modal-overlay active';
            overlay.innerHTML =
                '<div class="modal-box">' +
                    '<h3>Confirm Action</h3>' +
                    '<p>' + escapeHtml(match[1]) + '</p>' +
                    '<div class="modal-actions">' +
                        '<button class="button-secondary" data-dismiss>Cancel</button>' +
                        '<button class="button-danger" data-confirm>Confirm</button>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(overlay);

            overlay.querySelector('[data-dismiss]').addEventListener('click', function () {
                document.body.removeChild(overlay);
            });

            overlay.querySelector('[data-confirm]').addEventListener('click', function () {
                document.body.removeChild(overlay);
                btn.removeAttribute('onclick');
                btn.click();
            });

            overlay.addEventListener('click', function (ev) {
                if (ev.target === overlay) document.body.removeChild(overlay);
            });
        });
    }

    function initCodeTextareas() {
        var textareas = document.querySelectorAll('textarea[name="ascii_plan"], textarea[name="plan_data"], textarea[name="path_data"]');
        for (var i = 0; i < textareas.length; i++) {
            textareas[i].classList.add('code-input');
        }
    }

    function initActiveNav() {
        var path = window.location.pathname;
        var links = document.querySelectorAll('.nav a');
        for (var i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href');
            if (href && path.indexOf(href) === 0 && href !== '/dashboard') {
                links[i].classList.add('active');
            }
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    App.escapeHtml = escapeHtml;

    App.modal = {
        show: function (title, bodyHtml, actions) {
            var overlay = document.createElement('div');
            overlay.className = 'modal-overlay';

            var actionsHtml = '';
            if (actions) {
                actionsHtml = '<div class="modal-actions">';
                for (var i = 0; i < actions.length; i++) {
                    actionsHtml += '<button class="' + (actions[i].className || '') + '" data-action="' + i + '">' + escapeHtml(actions[i].label) + '</button>';
                }
                actionsHtml += '</div>';
            }

            overlay.innerHTML =
                '<div class="modal-box">' +
                    '<h3>' + escapeHtml(title) + '</h3>' +
                    '<div>' + bodyHtml + '</div>' +
                    actionsHtml +
                '</div>';

            document.body.appendChild(overlay);

            requestAnimationFrame(function () {
                overlay.classList.add('active');
            });

            if (actions) {
                var buttons = overlay.querySelectorAll('[data-action]');
                for (var j = 0; j < buttons.length; j++) {
                    (function (idx) {
                        buttons[idx].addEventListener('click', function () {
                            if (actions[idx].onClick) actions[idx].onClick();
                            App.modal.close(overlay);
                        });
                    })(j);
                }
            }

            overlay.addEventListener('click', function (ev) {
                if (ev.target === overlay) App.modal.close(overlay);
            });

            return overlay;
        },
        close: function (overlay) {
            overlay.classList.remove('active');
            setTimeout(function () {
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            }, 200);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        initSidebarToggle();
        initAlertDismiss();
        initCodeTextareas();
        initActiveNav();
    });
})();
