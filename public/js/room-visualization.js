var App = window.App || {};

App.roomViz = (function () {
    'use strict';

    var STATUS_COLORS = {
        available: { bg: 'rgba(35,116,77,0.12)', border: '#23744d' },
        active: { bg: 'rgba(35,116,77,0.12)', border: '#23744d' },
        occupied: { bg: 'rgba(183,66,48,0.12)', border: '#b74230' },
        unavailable: { bg: 'rgba(183,66,48,0.12)', border: '#b74230' },
        hybrid: { bg: 'rgba(44,106,136,0.12)', border: '#2c6a88' },
        online: { bg: 'rgba(44,106,136,0.12)', border: '#2c6a88' },
        default: { bg: 'rgba(98,116,104,0.1)', border: '#627468' }
    };

    function getColor(status) {
        status = (status || '').toLowerCase();
        return STATUS_COLORS[status] || STATUS_COLORS['default'];
    }

    function createTooltip() {
        var tip = document.getElementById('room-tooltip');
        if (tip) return tip;

        tip = document.createElement('div');
        tip.id = 'room-tooltip';
        tip.style.cssText = 'position:fixed;z-index:200;background:#17261f;color:#fff;padding:8px 12px;' +
            'border-radius:8px;font-size:12px;pointer-events:none;opacity:0;transition:opacity 0.15s;' +
            'max-width:220px;line-height:1.4;';
        document.body.appendChild(tip);
        return tip;
    }

    function showTooltip(el, room) {
        var tip = createTooltip();
        var lines = [];
        lines.push('<strong>' + App.escapeHtml(room.name || room.code || 'Room') + '</strong>');
        if (room.capacity) lines.push('Capacity: ' + room.capacity);
        if (room.status) lines.push('Status: ' + room.status);
        if (room.room_type || room.delivery_mode) lines.push('Type: ' + (room.room_type || room.delivery_mode));
        if (room.floor_name) lines.push('Floor: ' + room.floor_name);

        tip.innerHTML = lines.join('<br>');
        tip.style.opacity = '1';

        var rect = el.getBoundingClientRect();
        tip.style.left = (rect.left + rect.width / 2) + 'px';
        tip.style.top = (rect.top - 8) + 'px';
        tip.style.transform = 'translate(-50%, -100%)';
    }

    function hideTooltip() {
        var tip = document.getElementById('room-tooltip');
        if (tip) tip.style.opacity = '0';
    }

    function colorizeRooms(container) {
        var roomEls = container.querySelectorAll('.plan-room');
        for (var i = 0; i < roomEls.length; i++) {
            var status = roomEls[i].getAttribute('data-status') || 'available';
            var color = getColor(status);
            roomEls[i].style.background = color.bg;
            roomEls[i].style.borderColor = color.border;
        }
    }

    function attachTooltips(container, rooms) {
        var roomMap = {};
        if (rooms) {
            for (var i = 0; i < rooms.length; i++) {
                roomMap[rooms[i].id] = rooms[i];
            }
        }

        var roomEls = container.querySelectorAll('.plan-room');
        for (var j = 0; j < roomEls.length; j++) {
            (function (el) {
                el.addEventListener('mouseenter', function () {
                    var id = el.getAttribute('data-room-id');
                    var room = roomMap[id] || {
                        name: el.textContent,
                        status: el.getAttribute('data-status') || '',
                        capacity: el.getAttribute('data-capacity') || ''
                    };
                    showTooltip(el, room);
                });
                el.addEventListener('mouseleave', hideTooltip);
                el.addEventListener('click', function () {
                    var id = el.getAttribute('data-room-id');
                    if (id) window.location.href = '/rooms/' + id;
                });
            })(roomEls[j]);
        }
    }

    function highlightRoom(container, roomId) {
        var roomEls = container.querySelectorAll('.plan-room');
        for (var i = 0; i < roomEls.length; i++) {
            roomEls[i].classList.remove('selected');
            if (roomEls[i].getAttribute('data-room-id') === String(roomId)) {
                roomEls[i].classList.add('selected');
                roomEls[i].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    function init() {
        var containers = document.querySelectorAll('.plan-container, .plan-canvas-wrap');
        for (var i = 0; i < containers.length; i++) {
            colorizeRooms(containers[i]);
            attachTooltips(containers[i], []);
        }
    }

    return {
        init: init,
        colorizeRooms: colorizeRooms,
        attachTooltips: attachTooltips,
        highlightRoom: highlightRoom,
        getColor: getColor
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    App.roomViz.init();
});
