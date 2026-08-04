document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('calendar');
    if (!el) return;

    if (typeof FullCalendar === 'undefined') {
        console.warn('FullCalendar is not loaded.');
        return;
    }

    var feedUrl = el.getAttribute('data-feed') || '';

    var calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,listMonth'
        },
        height: 'auto',
        events: feedUrl,
        eventClick: function (info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            }
        },
        eventDidMount: function (info) {

            var cat = info.event.extendedProps.category || '';
            if (cat) {
                info.el.setAttribute('title', cat.replace(/_/g, ' '));
            }
        }
    });

    calendar.render();
});
