var App = window.App || {};

App.buildingEditor = (function () {
    'use strict';

    var CELL = 30;
    var COLORS = {
        room: '#22543d',
        corridor: '#627468',
        entrance: '#2c6a88',
        stairs: '#b6771e',
        wall: '#17261f',
        empty: '#f3f5ef',
        grid: 'rgba(0,0,0,0.04)'
    };

    function parseAsciiPlan(ascii) {
        if (!ascii || typeof ascii !== 'string') return { grid: [], width: 0, height: 0 };
        var lines = ascii.split('\n');
        var height = lines.length;
        var width = 0;
        var grid = [];

        for (var y = 0; y < height; y++) {
            var row = [];
            if (lines[y].length > width) width = lines[y].length;
            for (var x = 0; x < lines[y].length; x++) {
                row.push(lines[y][x]);
            }
            grid.push(row);
        }
        return { grid: grid, width: width, height: height };
    }

    function charColor(ch) {
        switch (ch) {
            case '#': case '|': case '-': case '+': return COLORS.wall;
            case 'R': case 'r': return COLORS.room;
            case 'C': case 'c': return COLORS.corridor;
            case 'E': case 'e': return COLORS.entrance;
            case 'S': case 's': return COLORS.stairs;
            case '.': return '#ffffff';
            case ' ': return COLORS.empty;
            default: return '#dce3d8';
        }
    }

    function renderAsciiToCanvas(canvas, ascii) {
        var plan = parseAsciiPlan(ascii);
        if (plan.width === 0) return;

        var ctx = canvas.getContext('2d');
        canvas.width = plan.width * CELL;
        canvas.height = plan.height * CELL;

        ctx.fillStyle = COLORS.empty;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        for (var y = 0; y < plan.grid.length; y++) {
            for (var x = 0; x < plan.grid[y].length; x++) {
                var ch = plan.grid[y][x];
                ctx.fillStyle = charColor(ch);
                ctx.fillRect(x * CELL, y * CELL, CELL, CELL);

                if (ch !== ' ' && ch !== '.') {
                    ctx.strokeStyle = 'rgba(0,0,0,0.08)';
                    ctx.strokeRect(x * CELL, y * CELL, CELL, CELL);
                }

                if (/[A-Za-z0-9]/.test(ch) && ch !== ' ') {
                    ctx.fillStyle = '#fff';
                    ctx.font = 'bold 11px Consolas, monospace';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(ch, x * CELL + CELL / 2, y * CELL + CELL / 2);
                }
            }
        }
    }

    function renderRoomsOverlay(container, rooms, floorWidth, floorHeight) {
        if (!rooms || !rooms.length) return;

        var overlay = container.querySelector('.plan-rooms-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'plan-rooms-overlay';
            overlay.style.position = 'absolute';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = (floorWidth || 600) + 'px';
            overlay.style.height = (floorHeight || 400) + 'px';
            container.appendChild(overlay);
        }
        overlay.innerHTML = '';

        for (var i = 0; i < rooms.length; i++) {
            var room = rooms[i];
            var x = parseInt(room.position_x || room.x || 0, 10) * CELL;
            var y = parseInt(room.position_y || room.y || 0, 10) * CELL;
            var w = parseInt(room.width || 3, 10) * CELL;
            var h = parseInt(room.height || 2, 10) * CELL;

            var div = document.createElement('div');
            div.className = 'plan-room';
            div.style.left = x + 'px';
            div.style.top = y + 'px';
            div.style.width = w + 'px';
            div.style.height = h + 'px';
            div.setAttribute('data-room-id', room.id || '');
            div.setAttribute('title', (room.name || room.code || 'Room') + ' (cap: ' + (room.capacity || '?') + ')');
            div.textContent = room.code || room.name || room.id || '';

            var handle = document.createElement('div');
            handle.className = 'resize-handle';
            div.appendChild(handle);

            overlay.appendChild(div);
        }
    }

    function renderVerticalView(container, building) {
        if (!container || !building) return;

        var orientations = ['east', 'west', 'north', 'south'];
        var viewContent = '';

        for (var i = 0; i < orientations.length; i++) {
            var key = 'vertical_view_' + orientations[i];
            if (building[key]) {
                viewContent = building[key];
                break;
            }
        }

        if (!viewContent && building.schematic_description) {
            viewContent = building.schematic_description;
        }

        if (viewContent) {
            var pre = document.createElement('pre');
            pre.className = 'ascii-preview';
            pre.textContent = viewContent;
            container.appendChild(pre);
        }
    }

    function addLegend(container) {
        var legend = document.createElement('div');
        legend.className = 'plan-legend';

        var items = [
            { color: COLORS.wall, label: 'Wall' },
            { color: COLORS.room, label: 'Room' },
            { color: COLORS.corridor, label: 'Corridor' },
            { color: COLORS.entrance, label: 'Entrance' },
            { color: COLORS.stairs, label: 'Stairs' }
        ];

        for (var i = 0; i < items.length; i++) {
            var item = document.createElement('span');
            item.className = 'plan-legend-item';
            item.innerHTML = '<span class="plan-legend-swatch" style="background:' + items[i].color + '"></span>' + items[i].label;
            legend.appendChild(item);
        }

        container.appendChild(legend);
    }

    function init() {
        var canvases = document.querySelectorAll('[data-ascii-plan]');
        for (var i = 0; i < canvases.length; i++) {
            var ascii = canvases[i].getAttribute('data-ascii-plan');
            if (ascii) {
                renderAsciiToCanvas(canvases[i], ascii);
            }
        }

        var planContainers = document.querySelectorAll('.plan-container');
        for (var j = 0; j < planContainers.length; j++) {
            addLegend(planContainers[j]);
        }

        var asciiTextareas = document.querySelectorAll('textarea[name="ascii_plan"]');
        for (var k = 0; k < asciiTextareas.length; k++) {
            setupLivePreview(asciiTextareas[k]);
        }
    }

    function setupLivePreview(textarea) {
        var wrapper = textarea.closest('.field') || textarea.parentElement;
        var canvas = document.createElement('canvas');
        canvas.className = 'plan-canvas';
        canvas.style.marginTop = '10px';
        canvas.style.borderRadius = '10px';
        canvas.style.border = '1px solid var(--line)';

        wrapper.appendChild(canvas);

        function update() {
            renderAsciiToCanvas(canvas, textarea.value);
        }

        textarea.addEventListener('input', update);
        update();
    }

    return {
        init: init,
        renderAsciiToCanvas: renderAsciiToCanvas,
        renderRoomsOverlay: renderRoomsOverlay,
        renderVerticalView: renderVerticalView,
        parseAsciiPlan: parseAsciiPlan,
        CELL: CELL
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    App.buildingEditor.init();
});
