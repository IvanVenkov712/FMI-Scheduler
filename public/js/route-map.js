var App = window.App || {};

App.routeMap = (function () {
    'use strict';

    var SVG_NS = 'http://www.w3.org/2000/svg';
    var NODE_RADIUS = 36;
    var PADDING = 60;

    var BUILDING_POSITIONS = {
        'fmi': { x: 0, y: 0 },
        'rectorat': { x: 1, y: 0 },
        'block2': { x: 0.5, y: 1 }
    };

    function createSvg(width, height) {
        var svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('width', width);
        svg.setAttribute('height', height);
        svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
        svg.style.width = '100%';
        svg.style.height = 'auto';
        svg.style.maxHeight = '500px';
        return svg;
    }

    function drawNode(svg, x, y, label, isHighlighted) {
        var g = document.createElementNS(SVG_NS, 'g');

        var circle = document.createElementNS(SVG_NS, 'circle');
        circle.setAttribute('cx', x);
        circle.setAttribute('cy', y);
        circle.setAttribute('r', NODE_RADIUS);
        circle.setAttribute('fill', isHighlighted ? '#22543d' : '#fff');
        circle.setAttribute('stroke', '#22543d');
        circle.setAttribute('stroke-width', '2');
        g.appendChild(circle);

        var text = document.createElementNS(SVG_NS, 'text');
        text.setAttribute('x', x);
        text.setAttribute('y', y + 4);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('fill', isHighlighted ? '#fff' : '#17261f');
        text.setAttribute('font-size', '12');
        text.setAttribute('font-weight', '700');
        text.textContent = label || '';
        g.appendChild(text);

        svg.appendChild(g);
        return g;
    }

    function drawEdge(svg, x1, y1, x2, y2, label, isHighlighted) {
        var g = document.createElementNS(SVG_NS, 'g');

        var line = document.createElementNS(SVG_NS, 'line');
        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('stroke', isHighlighted ? '#22543d' : '#d8dfd3');
        line.setAttribute('stroke-width', isHighlighted ? '3' : '2');
        if (!isHighlighted) line.setAttribute('stroke-dasharray', '6,4');
        g.appendChild(line);

        if (label) {
            var mx = (x1 + x2) / 2;
            var my = (y1 + y2) / 2 - 8;

            var bg = document.createElementNS(SVG_NS, 'rect');
            var textWidth = label.length * 6.5 + 12;
            bg.setAttribute('x', mx - textWidth / 2);
            bg.setAttribute('y', my - 10);
            bg.setAttribute('width', textWidth);
            bg.setAttribute('height', 20);
            bg.setAttribute('rx', '6');
            bg.setAttribute('fill', isHighlighted ? '#22543d' : '#f3f5ef');
            bg.setAttribute('stroke', isHighlighted ? '#22543d' : '#d8dfd3');
            bg.setAttribute('stroke-width', '1');
            g.appendChild(bg);

            var text = document.createElementNS(SVG_NS, 'text');
            text.setAttribute('x', mx);
            text.setAttribute('y', my + 3);
            text.setAttribute('text-anchor', 'middle');
            text.setAttribute('fill', isHighlighted ? '#fff' : '#627468');
            text.setAttribute('font-size', '11');
            text.setAttribute('font-weight', '600');
            text.textContent = label;
            g.appendChild(text);
        }

        svg.appendChild(g);
        return g;
    }

    function renderBuildingMap(container, buildings, routes, highlightRouteId) {
        if (!container) return;
        container.innerHTML = '';

        var width = 500;
        var height = 350;
        var svg = createSvg(width, height);

        var nodePositions = {};
        var cols = Math.ceil(Math.sqrt(buildings.length || 1));

        for (var i = 0; i < buildings.length; i++) {
            var code = (buildings[i].code || '').toLowerCase();
            var preset = BUILDING_POSITIONS[code];
            var col, row;

            if (preset) {
                col = preset.x;
                row = preset.y;
            } else {
                col = i % cols;
                row = Math.floor(i / cols);
            }

            var x = PADDING + col * ((width - 2 * PADDING) / Math.max(cols - 1, 1));
            var y = PADDING + row * ((height - 2 * PADDING) / Math.max(Math.ceil(buildings.length / cols) - 1, 1));

            if (buildings.length === 1) {
                x = width / 2;
                y = height / 2;
            }

            nodePositions[buildings[i].id] = { x: x, y: y, name: buildings[i].name || buildings[i].code || '' };
        }

        if (routes) {
            for (var j = 0; j < routes.length; j++) {
                var route = routes[j];
                var fromId = route.from_building_id;
                var toId = route.to_building_id;

                if (!nodePositions[fromId] || !nodePositions[toId]) continue;

                var from = nodePositions[fromId];
                var to = nodePositions[toId];
                var label = route.walking_duration ? route.walking_duration + ' min' : route.name || '';
                var isHL = String(route.id) === String(highlightRouteId);

                drawEdge(svg, from.x, from.y, to.x, to.y, label, isHL);
            }
        }

        for (var id in nodePositions) {
            if (!nodePositions.hasOwnProperty(id)) continue;
            var pos = nodePositions[id];
            drawNode(svg, pos.x, pos.y, pos.name, false);
        }

        container.appendChild(svg);
    }

    function renderSteps(container, steps) {
        if (!container || !steps || !steps.length) return;

        var list = document.createElement('ol');
        list.style.cssText = 'padding-left:20px;margin:0;display:grid;gap:8px;';

        for (var i = 0; i < steps.length; i++) {
            var li = document.createElement('li');
            li.style.cssText = 'padding:8px 12px;background:#f8faf6;border:1px solid var(--line);border-radius:8px;';
            var label = steps[i].label || steps[i].type || 'Step ' + (i + 1);
            var notes = steps[i].notes ? ' - ' + steps[i].notes : '';
            li.textContent = label + notes;
            list.appendChild(li);
        }

        container.appendChild(list);
    }

    function init() {
        var mapContainer = document.getElementById('route-map-svg');
        if (!mapContainer) return;

        var dataEl = document.getElementById('route-map-data');
        if (!dataEl) return;

        var data = {};
        try { data = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }

        renderBuildingMap(
            mapContainer,
            data.buildings || [],
            data.routes || [],
            data.highlightRouteId || null
        );

        var stepsContainer = document.getElementById('route-steps-list');
        if (stepsContainer && data.steps) {
            renderSteps(stepsContainer, data.steps);
        }
    }

    return {
        init: init,
        renderBuildingMap: renderBuildingMap,
        renderSteps: renderSteps
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    App.routeMap.init();
});
