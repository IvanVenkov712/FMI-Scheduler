var App = window.App || {};

App.allocation = (function () {
    'use strict';

    function initSearch() {
        var form = document.getElementById('allocation-search-form');
        var resultsContainer = document.getElementById('allocation-results');
        if (!form || !resultsContainer) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var params = new URLSearchParams(new FormData(form));
            resultsContainer.innerHTML = '<div class="text-center"><span class="spinner"></span> Searching...</div>';

            App.api.get('/api/allocation/search?' + params.toString())
                .then(function (data) {
                    renderSearchResults(resultsContainer, data);
                })
                .catch(function (err) {
                    resultsContainer.innerHTML = '<div class="alert alert-danger">' +
                        App.escapeHtml(err.message || 'Search failed.') + '</div>';
                });
        });
    }

    function renderSearchResults(container, data) {
        container.innerHTML = '';

        var rooms = data.rooms || data.results || [];
        if (rooms.length === 0) {
            container.innerHTML = '<div class="empty">No suitable rooms found for the given criteria.</div>';
            return;
        }

        var html = '<div class="table-wrap"><table>' +
            '<thead><tr><th>Room</th><th>Building</th><th>Capacity</th><th>Score</th><th>Explanation</th><th></th></tr></thead>' +
            '<tbody>';

        for (var i = 0; i < rooms.length; i++) {
            var room = rooms[i];
            html += '<tr>' +
                '<td><a href="/rooms/' + App.escapeHtml(String(room.id || room.room_id || '')) + '">' +
                    App.escapeHtml(room.name || room.code || 'Room') + '</a></td>' +
                '<td>' + App.escapeHtml(room.building_name || room.building_code || '-') + '</td>' +
                '<td>' + App.escapeHtml(String(room.capacity || '-')) + '</td>' +
                '<td><strong>' + App.escapeHtml(String(room.score != null ? room.score : '-')) + '</strong></td>' +
                '<td class="text-muted">' + App.escapeHtml(room.explanation || room.reason || '-') + '</td>' +
                '<td><a class="button button-link" href="/rooms/' + App.escapeHtml(String(room.id || room.room_id || '')) + '/availability">Check</a></td>' +
                '</tr>';
        }

        html += '</tbody></table></div>';
        container.innerHTML = html;
        container.classList.add('fade-in');
    }

    function initAutoAssign() {
        var form = document.getElementById('auto-assign-form');
        var resultContainer = document.getElementById('auto-assign-result');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var scheduleId = form.querySelector('[name="schedule_id"]');
            var url = '/api/allocation/auto-assign';
            if (scheduleId && scheduleId.value) {
                url += '/' + scheduleId.value;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Running...';
            }

            App.api.post(url, {})
                .then(function (data) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Run Auto-Assignment';
                    }

                    if (data.run_id) {
                        window.location.href = '/allocation/result/' + data.run_id;
                    } else if (resultContainer) {
                        resultContainer.innerHTML = '<div class="alert alert-success">Auto-assignment completed.</div>';
                        if (data.stats) {
                            resultContainer.innerHTML += '<pre class="code">' + App.escapeHtml(JSON.stringify(data.stats, null, 2)) + '</pre>';
                        }
                    }
                })
                .catch(function (err) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Run Auto-Assignment';
                    }
                    if (resultContainer) {
                        resultContainer.innerHTML = '<div class="alert alert-danger">' +
                            App.escapeHtml(err.message || 'Auto-assignment failed.') + '</div>';
                    }
                });
        });
    }

    function init() {
        initSearch();
        initAutoAssign();
    }

    return { init: init };
})();

document.addEventListener('DOMContentLoaded', function () {
    App.allocation.init();
});
