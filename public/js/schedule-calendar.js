var App = window.App || {};

App.calendar = (function () {
    'use strict';

    var HOUR_HEIGHT = 48;
    var START_HOUR = 7;
    var END_HOUR = 21;
    var DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    function parseDate(str) {
        if (!str) return null;
        var d = new Date(str.replace(' ', 'T'));
        return isNaN(d.getTime()) ? null : d;
    }

    function formatDate(d) {
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        return dd + '.' + mm;
    }

    function mondayOf(date) {
        var d = new Date(date);
        var day = d.getDay();
        var diff = d.getDate() - day + (day === 0 ? -6 : 1);
        d.setDate(diff);
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function addDays(date, n) {
        var d = new Date(date);
        d.setDate(d.getDate() + n);
        return d;
    }

    function eventTypeClass(type) {
        return 'event-type-' + (type || 'other');
    }

    function render(container, events, weekStart) {
        if (!container) return;
        weekStart = weekStart || mondayOf(new Date());
        container.innerHTML = '';

        var header = document.createElement('div');
        header.className = 'calendar-header';

        var title = document.createElement('h3');
        title.textContent = formatDate(weekStart) + ' - ' + formatDate(addDays(weekStart, 6));

        var nav = document.createElement('div');
        nav.className = 'calendar-nav';

        var prevBtn = document.createElement('button');
        prevBtn.textContent = '← Prev';
        prevBtn.addEventListener('click', function () {
            render(container, events, addDays(weekStart, -7));
        });

        var todayBtn = document.createElement('button');
        todayBtn.textContent = 'Today';
        todayBtn.addEventListener('click', function () {
            render(container, events, mondayOf(new Date()));
        });

        var nextBtn = document.createElement('button');
        nextBtn.textContent = 'Next →';
        nextBtn.addEventListener('click', function () {
            render(container, events, addDays(weekStart, 7));
        });

        nav.appendChild(prevBtn);
        nav.appendChild(todayBtn);
        nav.appendChild(nextBtn);
        header.appendChild(title);
        header.appendChild(nav);
        container.appendChild(header);

        var grid = document.createElement('div');
        grid.className = 'calendar-grid';

        var cornerHeader = document.createElement('div');
        cornerHeader.className = 'calendar-day-header';
        grid.appendChild(cornerHeader);

        for (var d = 0; d < 7; d++) {
            var dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day-header';
            var dayDate = addDays(weekStart, d);
            dayHeader.textContent = DAYS[d] + ' ' + formatDate(dayDate);
            grid.appendChild(dayHeader);
        }

        var totalHours = END_HOUR - START_HOUR;

        var timeCol = document.createElement('div');
        timeCol.className = 'calendar-time-col';
        for (var h = START_HOUR; h < END_HOUR; h++) {
            var slot = document.createElement('div');
            slot.className = 'calendar-time-slot';
            slot.textContent = String(h).padStart(2, '0') + ':00';
            timeCol.appendChild(slot);
        }
        grid.appendChild(timeCol);

        var weekEnd = addDays(weekStart, 7);

        for (var dayIdx = 0; dayIdx < 7; dayIdx++) {
            var dayCol = document.createElement('div');
            dayCol.className = 'calendar-day-col';
            dayCol.style.height = (totalHours * HOUR_HEIGHT) + 'px';

            for (var hh = START_HOUR; hh < END_HOUR; hh++) {
                var hourLine = document.createElement('div');
                hourLine.className = 'calendar-hour-line';
                hourLine.style.top = ((hh - START_HOUR) * HOUR_HEIGHT) + 'px';
                dayCol.appendChild(hourLine);
            }

            var currentDay = addDays(weekStart, dayIdx);
            var dayEvents = filterEventsForDay(events, currentDay);

            for (var e = 0; e < dayEvents.length; e++) {
                var ev = dayEvents[e];
                var startDate = parseDate(ev.starts_at);
                var endDate = parseDate(ev.ends_at);
                if (!startDate || !endDate) continue;

                var startMinutes = startDate.getHours() * 60 + startDate.getMinutes();
                var endMinutes = endDate.getHours() * 60 + endDate.getMinutes();
                var topPx = ((startMinutes - START_HOUR * 60) / 60) * HOUR_HEIGHT;
                var heightPx = ((endMinutes - startMinutes) / 60) * HOUR_HEIGHT;

                if (heightPx < 20) heightPx = 20;

                var block = document.createElement('a');
                block.className = 'calendar-event ' + eventTypeClass(ev.type || ev.event_type);
                block.style.top = topPx + 'px';
                block.style.height = heightPx + 'px';
                block.href = '/events/' + (ev.id || ev.event_id || '');

                var titleSpan = document.createElement('span');
                titleSpan.className = 'event-title';
                titleSpan.textContent = ev.title || ev.course_name || 'Event';
                block.appendChild(titleSpan);

                if (ev.room_code || ev.room_name) {
                    var roomSpan = document.createElement('span');
                    roomSpan.className = 'event-room';
                    roomSpan.textContent = ev.room_code || ev.room_name;
                    block.appendChild(roomSpan);
                }

                dayCol.appendChild(block);
            }

            var today = new Date();
            if (currentDay.toDateString() === today.toDateString()) {
                var nowMinutes = today.getHours() * 60 + today.getMinutes();
                var nowTop = ((nowMinutes - START_HOUR * 60) / 60) * HOUR_HEIGHT;
                if (nowTop >= 0 && nowTop <= totalHours * HOUR_HEIGHT) {
                    var nowLine = document.createElement('div');
                    nowLine.className = 'calendar-today-line';
                    nowLine.style.top = nowTop + 'px';
                    dayCol.appendChild(nowLine);
                }
            }

            grid.appendChild(dayCol);
        }

        container.appendChild(grid);
    }

    function filterEventsForDay(events, day) {
        if (!events || !events.length) return [];
        var dayStr = day.getFullYear() + '-' +
            String(day.getMonth() + 1).padStart(2, '0') + '-' +
            String(day.getDate()).padStart(2, '0');

        return events.filter(function (ev) {
            var start = ev.starts_at || ev.start_time || '';
            return start.substring(0, 10) === dayStr;
        });
    }

    function init() {
        var container = document.getElementById('schedule-calendar');
        if (!container) return;

        var dataEl = document.getElementById('calendar-data');
        var events = [];
        if (dataEl) {
            try { events = JSON.parse(dataEl.textContent || '[]'); } catch (e) { events = []; }
        }

        container.classList.add('calendar-container');
        render(container, events);
    }

    return { init: init, render: render };
})();

document.addEventListener('DOMContentLoaded', function () {
    App.calendar.init();
});
