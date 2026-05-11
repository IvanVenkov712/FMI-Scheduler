var App = window.App || {};

App.dragdrop = (function () {
    'use strict';

    var GRID_SNAP = 30;
    var state = {
        dragging: null,
        resizing: null,
        offsetX: 0,
        offsetY: 0,
        startW: 0,
        startH: 0,
        startX: 0,
        startY: 0,
        container: null
    };

    function snap(val) {
        return Math.round(val / GRID_SNAP) * GRID_SNAP;
    }

    function getContainerOffset(container) {
        var rect = container.getBoundingClientRect();
        return { left: rect.left + window.scrollX, top: rect.top + window.scrollY };
    }

    function initContainer(container) {
        if (!container) return;
        state.container = container;

        container.addEventListener('mousedown', function (e) {
            var resizeHandle = e.target.closest('.resize-handle');
            var roomEl = e.target.closest('.plan-room');
            if (!roomEl) return;

            e.preventDefault();
            var rect = roomEl.getBoundingClientRect();

            if (resizeHandle) {
                state.resizing = roomEl;
                state.startW = roomEl.offsetWidth;
                state.startH = roomEl.offsetHeight;
                state.startX = e.clientX;
                state.startY = e.clientY;
                roomEl.classList.add('dragging');
            } else {
                state.dragging = roomEl;
                state.offsetX = e.clientX - rect.left;
                state.offsetY = e.clientY - rect.top;
                roomEl.classList.add('dragging');
            }
        });

        document.addEventListener('mousemove', function (e) {
            if (state.dragging) {
                var containerRect = container.getBoundingClientRect();
                var x = e.clientX - containerRect.left - state.offsetX;
                var y = e.clientY - containerRect.top - state.offsetY;

                x = snap(Math.max(0, Math.min(x, containerRect.width - state.dragging.offsetWidth)));
                y = snap(Math.max(0, Math.min(y, containerRect.height - state.dragging.offsetHeight)));

                state.dragging.style.left = x + 'px';
                state.dragging.style.top = y + 'px';
            }

            if (state.resizing) {
                var dw = e.clientX - state.startX;
                var dh = e.clientY - state.startY;
                var newW = snap(Math.max(GRID_SNAP, state.startW + dw));
                var newH = snap(Math.max(GRID_SNAP, state.startH + dh));

                state.resizing.style.width = newW + 'px';
                state.resizing.style.height = newH + 'px';
            }
        });

        document.addEventListener('mouseup', function () {
            if (state.dragging) {
                state.dragging.classList.remove('dragging');
                state.dragging = null;
            }
            if (state.resizing) {
                state.resizing.classList.remove('dragging');
                state.resizing = null;
            }
        });
    }

    function collectPositions(container) {
        var rooms = container.querySelectorAll('.plan-room');
        var positions = [];

        for (var i = 0; i < rooms.length; i++) {
            positions.push({
                room_id: rooms[i].getAttribute('data-room-id') || '',
                x: Math.round(parseInt(rooms[i].style.left, 10) / GRID_SNAP),
                y: Math.round(parseInt(rooms[i].style.top, 10) / GRID_SNAP),
                width: Math.round(rooms[i].offsetWidth / GRID_SNAP),
                height: Math.round(rooms[i].offsetHeight / GRID_SNAP)
            });
        }

        return positions;
    }

    function addRoom(container, options) {
        options = options || {};
        var div = document.createElement('div');
        div.className = 'plan-room';
        div.style.left = snap(options.x || 0) + 'px';
        div.style.top = snap(options.y || 0) + 'px';
        div.style.width = (options.width || 3) * GRID_SNAP + 'px';
        div.style.height = (options.height || 2) * GRID_SNAP + 'px';
        div.setAttribute('data-room-id', options.id || 'new-' + Date.now());
        div.textContent = options.label || 'New';

        var handle = document.createElement('div');
        handle.className = 'resize-handle';
        div.appendChild(handle);

        var overlay = container.querySelector('.plan-rooms-overlay');
        if (overlay) {
            overlay.appendChild(div);
        } else {
            container.appendChild(div);
        }

        return div;
    }

    function savePlan(floorId, container) {
        if (!floorId || !container) return Promise.reject('Missing floor ID');

        var positions = collectPositions(container);
        var asciiTextarea = document.querySelector('textarea[name="ascii_plan"]');
        var planDataTextarea = document.querySelector('textarea[name="plan_data"]');

        var body = {
            points: positions,
            ascii_plan: asciiTextarea ? asciiTextarea.value : '',
            plan_data: planDataTextarea ? planDataTextarea.value : ''
        };

        return App.api.put('/floors/' + floorId + '/plan', body).then(function (result) {
            var alerts = document.querySelectorAll('.alert-success');
            if (alerts.length === 0) {
                var alert = document.createElement('div');
                alert.className = 'alert alert-success fade-in';
                alert.textContent = 'Plan saved successfully.';
                var content = document.querySelector('.content');
                if (content) content.insertBefore(alert, content.querySelector('.page-head').nextSibling);
            }
            return result;
        });
    }

    function initToolbar(container, floorId) {
        var toolbar = container.querySelector('.plan-toolbar');
        if (!toolbar) return;

        var saveBtn = toolbar.querySelector('[data-action="save"]');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var canvasWrap = container.querySelector('.plan-canvas-wrap');
                savePlan(floorId, canvasWrap || container);
            });
        }

        var addBtn = toolbar.querySelector('[data-action="add-room"]');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var canvasWrap = container.querySelector('.plan-canvas-wrap');
                addRoom(canvasWrap || container, { x: 30, y: 30, label: '?' });
            });
        }
    }

    function init() {
        var containers = document.querySelectorAll('.plan-canvas-wrap');
        for (var i = 0; i < containers.length; i++) {
            initContainer(containers[i]);
        }

        var planContainers = document.querySelectorAll('.plan-container');
        for (var j = 0; j < planContainers.length; j++) {
            var floorId = planContainers[j].getAttribute('data-floor-id');
            initToolbar(planContainers[j], floorId);
        }
    }

    return {
        init: init,
        initContainer: initContainer,
        collectPositions: collectPositions,
        addRoom: addRoom,
        savePlan: savePlan
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    App.dragdrop.init();
});
