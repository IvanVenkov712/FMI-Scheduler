var App = window.App || {};

App.importExport = (function () {
    'use strict';

    function initFileDropZone() {
        var fileInputs = document.querySelectorAll('input[type="file"]');

        for (var i = 0; i < fileInputs.length; i++) {
            (function (input) {
                var parent = input.closest('.field') || input.parentElement;
                if (!parent || parent.querySelector('.file-drop-zone')) return;

                var zone = document.createElement('div');
                zone.className = 'file-drop-zone';
                zone.innerHTML =
                    '<span class="drop-icon">&#128196;</span>' +
                    '<span class="drop-label">Drop file here or click to browse</span>' +
                    '<span class="drop-hint">CSV, JSON, XML, TXT, ASCII</span>' +
                    '<span class="file-name hidden"></span>';

                input.style.position = 'absolute';
                input.style.inset = '0';
                input.style.opacity = '0';
                input.style.cursor = 'pointer';

                zone.appendChild(input);
                parent.appendChild(zone);

                zone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    zone.classList.add('dragover');
                });

                zone.addEventListener('dragleave', function () {
                    zone.classList.remove('dragover');
                });

                zone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    zone.classList.remove('dragover');
                    if (e.dataTransfer.files.length > 0) {
                        input.files = e.dataTransfer.files;
                        updateFileName(zone, input);
                        autoDetectFormat(input);
                    }
                });

                input.addEventListener('change', function () {
                    updateFileName(zone, input);
                    autoDetectFormat(input);
                });
            })(fileInputs[i]);
        }
    }

    function updateFileName(zone, input) {
        var nameEl = zone.querySelector('.file-name');
        if (!nameEl) return;

        if (input.files && input.files.length > 0) {
            nameEl.textContent = input.files[0].name;
            nameEl.classList.remove('hidden');
        } else {
            nameEl.classList.add('hidden');
        }
    }

    function autoDetectFormat(input) {
        if (!input.files || !input.files.length) return;

        var fileName = input.files[0].name;
        var ext = fileName.split('.').pop().toLowerCase();
        var formatSelect = document.querySelector('select[name="format"]');
        if (!formatSelect) return;

        var formatMap = {
            'csv': 'csv',
            'json': 'json',
            'xml': 'xml',
            'txt': 'txt',
            'ascii': 'ascii'
        };

        if (formatMap[ext]) {
            formatSelect.value = formatMap[ext];
        }
    }

    function initImportForm() {
        var form = document.querySelector('form[action*="import"]');
        if (!form) return;

        form.addEventListener('submit', function () {
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Importing...';
            }
            form.classList.add('form-loading');
        });
    }

    function initExportForm() {
        var form = document.querySelector('form[action*="export"]');
        if (!form) return;

        form.addEventListener('submit', function () {
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Exporting...';
                setTimeout(function () {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Export';
                }, 3000);
            }
        });
    }

    function init() {
        initFileDropZone();
        initImportForm();
        initExportForm();
    }

    return { init: init };
})();

document.addEventListener('DOMContentLoaded', function () {
    App.importExport.init();
});
