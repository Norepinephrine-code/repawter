
(function (global, factory) {
  'use strict';

  var api = factory();

  if (typeof module === 'object' && module !== null && typeof module.exports === 'object') {
    module.exports = api;
  } else {
    global.PawCalendar = api;
    global.document.addEventListener('DOMContentLoaded', function () {
      api.mount(global.document, global.FullCalendar, global.console);
    });
  }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';

  
  function categoryLabel(category) {
    if (typeof category !== 'string' || category.trim() === '') {
      return '';
    }
    return category.replace(/_/g, ' ');
  }

  
  function handleEventClick(info, location) {
    if (!info || !info.event || !info.event.url) {
      return false;
    }

    if (info.jsEvent && typeof info.jsEvent.preventDefault === 'function') {
      info.jsEvent.preventDefault();
    }

    location.href = info.event.url;
    return true;
  }

  
  function decorateEvent(info) {
    if (!info || !info.el || !info.event) {
      return '';
    }

    var label = categoryLabel(
      info.event.extendedProps ? info.event.extendedProps.category : ''
    );

    if (label !== '') {
      info.el.setAttribute('title', label);
    }

    return label;
  }

  
  function buildOptions(el, location) {
    return {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listMonth'
      },
      height: 'auto',

      firstDay: 0,
      events: el.getAttribute('data-feed') || '',
      eventClick: function (info) {
        return handleEventClick(info, location);
      },
      eventDidMount: decorateEvent
    };
  }

  
  function mount(doc, FullCalendarLib, consoleRef) {
    var el = doc.getElementById('calendar');
    if (!el) {
      return null;
    }

    if (!FullCalendarLib || typeof FullCalendarLib.Calendar !== 'function') {
      if (consoleRef && typeof consoleRef.warn === 'function') {
        consoleRef.warn('FullCalendar is not loaded; the calendar will not render.');
      }
      return null;
    }

    var location = (doc.defaultView && doc.defaultView.location) || { href: '' };
    var calendar = new FullCalendarLib.Calendar(el, buildOptions(el, location));
    calendar.render();

    return calendar;
  }

  return {
    categoryLabel: categoryLabel,
    handleEventClick: handleEventClick,
    decorateEvent: decorateEvent,
    buildOptions: buildOptions,
    mount: mount
  };
});
