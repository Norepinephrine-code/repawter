
const PawCalendar = require('../../assets/js/calendar-init.js');

describe('categoryLabel', () => {
  test.each([
    ['vaccination_drive', 'vaccination drive'],
    ['tnr_schedule', 'tnr schedule'],
    ['welfare_operation', 'welfare operation'],
    ['general', 'general']
  ])('turns %s into a readable label', (input, expected) => {
    expect(PawCalendar.categoryLabel(input)).toBe(expected);
  });

  test.each([
    ['undefined', undefined],
    ['null', null],
    ['a number', 42],
    ['an empty string', ''],
    ['whitespace', '   ']
  ])('returns an empty string for %s', (_label, input) => {
    expect(PawCalendar.categoryLabel(input)).toBe('');
  });
});

describe('buildOptions', () => {
  function elementWithFeed(feed) {
    const el = document.createElement('div');
    if (feed !== null) {
      el.setAttribute('data-feed', feed);
    }
    return el;
  }

  test('reads the events URL from data-feed', () => {
    const opts = PawCalendar.buildOptions(elementWithFeed('/repawter/announcements/feed.php'), {});
    expect(opts.events).toBe('/repawter/announcements/feed.php');
  });

  test('falls back to an empty feed when the attribute is absent', () => {
    expect(PawCalendar.buildOptions(elementWithFeed(null), {}).events).toBe('');
  });

  test('opens on the month grid and offers a list view', () => {
    const opts = PawCalendar.buildOptions(elementWithFeed('/feed'), {});
    expect(opts.initialView).toBe('dayGridMonth');
    expect(opts.headerToolbar.right).toBe('dayGridMonth,listMonth');
    expect(opts.headerToolbar.left).toBe('prev,next today');
    expect(opts.height).toBe('auto');
  });

  test('wires eventClick to the supplied location', () => {
    const location = { href: '' };
    const opts = PawCalendar.buildOptions(elementWithFeed('/feed'), location);

    const preventDefault = jest.fn();
    opts.eventClick({
      event: { url: '/repawter/announcements/view.php?id=7' },
      jsEvent: { preventDefault }
    });

    expect(location.href).toBe('/repawter/announcements/view.php?id=7');
    expect(preventDefault).toHaveBeenCalled();
  });
});

describe('handleEventClick', () => {
  test('navigates to the event URL and suppresses the default', () => {
    const location = { href: 'about:blank' };
    const preventDefault = jest.fn();

    const navigated = PawCalendar.handleEventClick(
      { event: { url: '/view.php?id=1' }, jsEvent: { preventDefault } },
      location
    );

    expect(navigated).toBe(true);
    expect(location.href).toBe('/view.php?id=1');
    expect(preventDefault).toHaveBeenCalled();
  });

  test.each([
    ['no info object', undefined],
    ['no event', {}],
    ['an event with no url', { event: {} }],
    ['an event with an empty url', { event: { url: '' } }]
  ])('does not navigate for %s', (_label, info) => {
    const location = { href: 'about:blank' };
    expect(PawCalendar.handleEventClick(info, location)).toBe(false);
    expect(location.href).toBe('about:blank');
  });

  test('navigates even when there is no underlying DOM event', () => {
    const location = { href: '' };
    expect(PawCalendar.handleEventClick({ event: { url: '/x' } }, location)).toBe(true);
    expect(location.href).toBe('/x');
  });
});

describe('decorateEvent', () => {
  test('sets a title attribute describing the category', () => {
    const el = document.createElement('div');
    const label = PawCalendar.decorateEvent({
      el,
      event: { extendedProps: { category: 'adoption_event' } }
    });

    expect(label).toBe('adoption event');
    expect(el.getAttribute('title')).toBe('adoption event');
  });

  test('leaves the element untouched when the event has no category', () => {
    const el = document.createElement('div');
    PawCalendar.decorateEvent({ el, event: { extendedProps: {} } });
    expect(el.hasAttribute('title')).toBe(false);
  });

  test('tolerates an event with no extendedProps at all', () => {
    const el = document.createElement('div');
    expect(PawCalendar.decorateEvent({ el, event: {} })).toBe('');
    expect(el.hasAttribute('title')).toBe(false);
  });

  test.each([
    ['no info', undefined],
    ['no element', { event: {} }],
    ['no event', { el: document.createElement('div') }]
  ])('returns an empty label for %s', (_label, info) => {
    expect(PawCalendar.decorateEvent(info)).toBe('');
  });
});

describe('mount', () => {
  
  function fakeLibrary() {
    const render = jest.fn();
    const Calendar = jest.fn(function (el, options) {
      this.el = el;
      this.options = options;
      this.render = render;
    });
    return { lib: { Calendar }, Calendar, render };
  }

  test('renders a calendar into #calendar', () => {
    document.body.innerHTML = '<div id="calendar" data-feed="/feed.php"></div>';
    const { lib, Calendar, render } = fakeLibrary();

    const calendar = PawCalendar.mount(document, lib, console);

    expect(calendar).not.toBeNull();
    expect(Calendar).toHaveBeenCalledTimes(1);
    expect(Calendar.mock.calls[0][0]).toBe(document.getElementById('calendar'));
    expect(Calendar.mock.calls[0][1].events).toBe('/feed.php');
    expect(render).toHaveBeenCalled();
  });

  test('does nothing on a page with no calendar element', () => {
    document.body.innerHTML = '<p>No calendar here</p>';
    const { lib, Calendar } = fakeLibrary();

    expect(PawCalendar.mount(document, lib, console)).toBeNull();
    expect(Calendar).not.toHaveBeenCalled();
  });

  test('warns instead of throwing when FullCalendar is missing', () => {
    document.body.innerHTML = '<div id="calendar" data-feed="/feed.php"></div>';
    const warn = jest.fn();

    expect(PawCalendar.mount(document, undefined, { warn })).toBeNull();
    expect(warn).toHaveBeenCalledWith(expect.stringContaining('FullCalendar'));
  });

  test('warns when the global exists but is not a usable library', () => {
    document.body.innerHTML = '<div id="calendar"></div>';
    const warn = jest.fn();

    expect(PawCalendar.mount(document, { Calendar: 'nope' }, { warn })).toBeNull();
    expect(warn).toHaveBeenCalled();
  });

  test('survives a missing console', () => {
    document.body.innerHTML = '<div id="calendar"></div>';
    expect(() => PawCalendar.mount(document, undefined, undefined)).not.toThrow();
  });
});
