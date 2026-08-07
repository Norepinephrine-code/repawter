
const PawApp = require('../../assets/js/app.js');

class FakeFileReader {
  constructor() {
    this.onload = null;
    this.readAsDataURL = jest.fn((file) => {
      this.lastFile = file;
    });
  }

  
  finish(result) {
    this.onload({ target: { result } });
  }
}

function setBody(html) {
  document.body.innerHTML = html;
}

function freshDoc(html) {
  const doc = document.implementation.createHTMLDocument('test');
  doc.body.innerHTML = html;
  return doc;
}

describe('confirmMessageFor', () => {
  test('uses the value of data-confirm', () => {
    setBody('<button id="b" data-confirm="Delete this report?"></button>');
    expect(PawApp.confirmMessageFor(document.getElementById('b')))
      .toBe('Delete this report?');
  });

  test.each([
    ['empty attribute', '<button id="b" data-confirm=""></button>'],
    ['whitespace only', '<button id="b" data-confirm="   "></button>']
  ])('falls back to the default message when the attribute is %s', (_label, html) => {
    setBody(html);
    expect(PawApp.confirmMessageFor(document.getElementById('b')))
      .toBe(PawApp.DEFAULT_CONFIRM_MESSAGE);
  });
});

describe('findConfirmable', () => {
  test('finds the attribute on an ancestor of the clicked node', () => {
    setBody('<a id="link" data-confirm="Sure?"><span id="icon">x</span></a>');
    const found = PawApp.findConfirmable(document.getElementById('icon'));
    expect(found).toBe(document.getElementById('link'));
  });

  test('returns null for a target that is not an element', () => {
    expect(PawApp.findConfirmable(null)).toBeNull();
    expect(PawApp.findConfirmable({})).toBeNull();
    expect(PawApp.findConfirmable(document.createTextNode('x'))).toBeNull();
  });

  test('returns null when nothing in the ancestry opts in', () => {
    setBody('<div><button id="b">Save</button></div>');
    expect(PawApp.findConfirmable(document.getElementById('b'))).toBeNull();
  });
});

describe('click confirmation', () => {
  test('lets the click through when the user accepts', () => {
    const doc = freshDoc('<a href="#gone" id="del" data-confirm="Archive?">Archive</a>');
    const confirmFn = jest.fn(() => true);
    PawApp.init(doc, { window, confirm: confirmFn });

    const event = new window.MouseEvent('click', { bubbles: true, cancelable: true });
    doc.getElementById('del').dispatchEvent(event);

    expect(confirmFn).toHaveBeenCalledWith('Archive?');
    expect(event.defaultPrevented).toBe(false);
  });

  test('cancels the click when the user declines', () => {
    const doc = freshDoc('<a href="#gone" id="del" data-confirm="Archive?">Archive</a>');
    PawApp.init(doc, { window, confirm: () => false });

    const event = new window.MouseEvent('click', { bubbles: true, cancelable: true });
    doc.getElementById('del').dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
  });

  test('does not prompt for elements without data-confirm', () => {
    const doc = freshDoc('<a href="#ok" id="plain">Go</a>');
    const confirmFn = jest.fn(() => true);
    PawApp.init(doc, { window, confirm: confirmFn });

    doc.getElementById('plain')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));

    expect(confirmFn).not.toHaveBeenCalled();
  });

  test('stops other handlers once the user declines', () => {
    const doc = freshDoc('<button id="del" data-confirm="Sure?">Delete</button>');
    const other = jest.fn();

    PawApp.init(doc, { window, confirm: () => false });
    doc.addEventListener('click', other);

    doc.getElementById('del')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));

    expect(other).not.toHaveBeenCalled();
  });

  test('handleConfirmClick reports whether the action may continue', () => {
    setBody('<button id="b" data-confirm="Sure?"></button>');
    const target = document.getElementById('b');
    const accepted = { target, preventDefault: jest.fn(), stopImmediatePropagation: jest.fn() };
    const declined = { target, preventDefault: jest.fn(), stopImmediatePropagation: jest.fn() };

    expect(PawApp.handleConfirmClick(accepted, () => true)).toBe(true);
    expect(accepted.preventDefault).not.toHaveBeenCalled();

    expect(PawApp.handleConfirmClick(declined, () => false)).toBe(false);
    expect(declined.preventDefault).toHaveBeenCalled();
    expect(declined.stopImmediatePropagation).toHaveBeenCalled();
  });

  test('tolerates an event whose target cannot stop propagation', () => {
    setBody('<button id="b" data-confirm="Sure?"></button>');
    const event = { target: document.getElementById('b'), preventDefault: jest.fn() };

    expect(() => PawApp.handleConfirmClick(event, () => false)).not.toThrow();
    expect(event.preventDefault).toHaveBeenCalled();
  });
});

describe('submit confirmation', () => {
  test('cancels the submit when the user declines', () => {
    const doc = freshDoc('<form id="f" data-confirm="Suspend this account?"><button>Go</button></form>');
    const confirmFn = jest.fn(() => false);
    PawApp.init(doc, { window, confirm: confirmFn });

    const event = new window.Event('submit', { bubbles: true, cancelable: true });
    doc.getElementById('f').dispatchEvent(event);

    expect(confirmFn).toHaveBeenCalledWith('Suspend this account?');
    expect(event.defaultPrevented).toBe(true);
  });

  test('allows the submit when the user accepts', () => {
    setBody('<form id="f" data-confirm="Suspend?"><button>Go</button></form>');
    const event = { target: document.getElementById('f'), preventDefault: jest.fn() };

    expect(PawApp.handleConfirmSubmit(event, () => true)).toBe(true);
    expect(event.preventDefault).not.toHaveBeenCalled();
  });

  test('ignores forms without data-confirm', () => {
    setBody('<form id="f"><button>Go</button></form>');
    const confirmFn = jest.fn(() => false);
    const event = { target: document.getElementById('f'), preventDefault: jest.fn() };

    expect(PawApp.handleConfirmSubmit(event, confirmFn)).toBe(true);
    expect(confirmFn).not.toHaveBeenCalled();
    expect(event.preventDefault).not.toHaveBeenCalled();
  });

  test('ignores a submit event with a non-element target', () => {
    expect(PawApp.handleConfirmSubmit({ target: null }, () => false)).toBe(true);
  });
});

describe('image preview', () => {
  function fileInputFixture() {
    setBody(
      '<input type="file" id="photo" data-preview="#preview">' +
      '<img id="preview" alt="">'
    );
    return {
      input: document.getElementById('photo'),
      preview: document.getElementById('preview')
    };
  }

  
  function attachFile(input, file) {
    Object.defineProperty(input, 'files', { value: file ? [file] : [], configurable: true });
  }

  test('renders the chosen image once the read completes', () => {
    const { input, preview } = fileInputFixture();
    attachFile(input, { type: 'image/png', name: 'dog.png' });

    let reader;
    const Ctor = function () { reader = new FakeFileReader(); return reader; };

    const started = PawApp.handleFilePreview({ target: input }, document, Ctor);
    expect(started).toBe(true);
    expect(reader.readAsDataURL).toHaveBeenCalled();

    reader.finish('data:image/png;base64,AAA');

    expect(preview.getAttribute('src')).toBe('data:image/png;base64,AAA');
    expect(preview.style.display).toBe('block');
    expect(preview.hasAttribute('hidden')).toBe(false);
  });

  test('hides the preview and reads nothing for a non-image file', () => {
    const { input, preview } = fileInputFixture();
    attachFile(input, { type: 'application/pdf', name: 'form.pdf' });
    const Ctor = jest.fn();

    expect(PawApp.handleFilePreview({ target: input }, document, Ctor)).toBe(false);
    expect(Ctor).not.toHaveBeenCalled();
    expect(preview.style.display).toBe('none');
    expect(preview.hasAttribute('hidden')).toBe(true);
  });

  test('hides the preview when the selection is cleared', () => {
    const { input, preview } = fileInputFixture();
    attachFile(input, null);

    expect(PawApp.handleFilePreview({ target: input }, document, FakeFileReader)).toBe(false);
    expect(preview.style.display).toBe('none');
  });

  test('does nothing when the input has no data-preview', () => {
    setBody('<input type="file" id="photo"><img id="preview">');
    const input = document.getElementById('photo');
    attachFile(input, { type: 'image/png' });

    expect(PawApp.handleFilePreview({ target: input }, document, FakeFileReader)).toBe(false);
  });

  test('does nothing when the preview target is missing from the page', () => {
    setBody('<input type="file" id="photo" data-preview="#nope">');
    const input = document.getElementById('photo');
    attachFile(input, { type: 'image/png' });

    expect(PawApp.handleFilePreview({ target: input }, document, FakeFileReader)).toBe(false);
  });

  test.each([
    ['a text input', '<input type="text" id="x" data-preview="#p"><img id="p">'],
    ['a select', '<select id="x" data-preview="#p"></select><img id="p">']
  ])('ignores change events from %s', (_label, html) => {
    setBody(html);
    expect(
      PawApp.handleFilePreview({ target: document.getElementById('x') }, document, FakeFileReader)
    ).toBe(false);
  });

  test('is wired up by init through a real change event', () => {
    const doc = freshDoc(
      '<input type="file" id="photo" data-preview="#preview"><img id="preview" alt="">'
    );
    const input = doc.getElementById('photo');
    attachFile(input, { type: 'image/jpeg', name: 'cat.jpg' });

    let reader;
    const Ctor = function () { reader = new FakeFileReader(); return reader; };
    PawApp.init(doc, { window, confirm: () => true, FileReader: Ctor });

    input.dispatchEvent(new window.Event('change', { bubbles: true }));
    reader.finish('data:image/jpeg;base64,BBB');

    expect(doc.getElementById('preview').getAttribute('src')).toBe('data:image/jpeg;base64,BBB');
  });
});

describe('alert auto-dismissal', () => {
  beforeEach(() => jest.useFakeTimers());
  afterEach(() => jest.useRealTimers());

  const ALERT = '<div class="alert alert-success alert-dismissible" id="a">Saved</div>';

  test('uses the Bootstrap Alert component when it is available', () => {
    setBody(ALERT);
    const close = jest.fn();
    const bootstrapRef = { Alert: { getOrCreateInstance: jest.fn(() => ({ close })) } };

    const scheduled = PawApp.autoDismissAlerts(document, window, bootstrapRef, 1000);
    expect(scheduled).toBe(1);

    jest.advanceTimersByTime(1000);
    expect(bootstrapRef.Alert.getOrCreateInstance).toHaveBeenCalled();
    expect(close).toHaveBeenCalled();
  });

  test('falls back to a manual fade when Bootstrap has not loaded', () => {
    setBody(ALERT);
    PawApp.autoDismissAlerts(document, window, undefined, 1000);

    jest.advanceTimersByTime(1000);
    const alert = document.getElementById('a');
    expect(alert.style.opacity).toBe('0');

    jest.advanceTimersByTime(500);
    expect(document.getElementById('a')).toBeNull();
  });

  test('leaves an alert alone while it holds keyboard focus', () => {
    setBody(
      '<div class="alert alert-danger alert-dismissible" id="a">' +
      'Failed <button id="close">Dismiss</button></div>'
    );
    document.getElementById('close').focus();

    PawApp.autoDismissAlerts(document, window, undefined, 1000);
    jest.advanceTimersByTime(1000);

    expect(document.getElementById('a').style.opacity).toBe('');
  });

  test('ignores alerts that are not dismissible', () => {
    setBody('<div class="alert alert-info" id="a">Note</div>');
    expect(PawApp.autoDismissAlerts(document, window, undefined, 1000)).toBe(0);
  });

  test('schedules every dismissible alert on the page', () => {
    setBody(
      '<div class="alert alert-success alert-dismissible">One</div>' +
      '<div class="alert alert-warning alert-dismissible">Two</div>' +
      '<div class="alert alert-info">Three</div>'
    );
    expect(PawApp.autoDismissAlerts(document, window, undefined, 1000)).toBe(2);
  });

  test('dismissAlert reports which strategy it used', () => {
    setBody(ALERT);
    const alert = document.getElementById('a');
    const bootstrapRef = { Alert: { getOrCreateInstance: () => ({ close: jest.fn() }) } };

    expect(PawApp.dismissAlert(alert, bootstrapRef, window)).toBe('bootstrap');
    expect(PawApp.dismissAlert(alert, null, window)).toBe('manual');
  });

  test('uses the default delay when none is given', () => {
    setBody(ALERT);
    PawApp.autoDismissAlerts(document, window, undefined);

    jest.advanceTimersByTime(PawApp.ALERT_DISMISS_DELAY_MS - 1);
    expect(document.getElementById('a').style.opacity).toBe('');

    jest.advanceTimersByTime(1);
    expect(document.getElementById('a').style.opacity).toBe('0');
  });
});

describe('init', () => {
  test('defers start until DOMContentLoaded while the document is loading', () => {
    const listeners = {};
    const fakeDoc = {
      readyState: 'loading',
      addEventListener: jest.fn((type, fn) => { listeners[type] = fn; }),
      querySelectorAll: jest.fn(() => []),
      querySelector: jest.fn()
    };

    PawApp.init(fakeDoc, { window, confirm: () => true });

    expect(fakeDoc.querySelectorAll).not.toHaveBeenCalled();
    listeners.DOMContentLoaded();
    expect(fakeDoc.querySelectorAll).toHaveBeenCalledWith('.alert.alert-dismissible');
  });

  test('starts immediately once the document is ready', () => {
    const fakeDoc = {
      readyState: 'complete',
      addEventListener: jest.fn(),
      querySelectorAll: jest.fn(() => []),
      querySelector: jest.fn()
    };

    PawApp.init(fakeDoc, { window, confirm: () => true });
    expect(fakeDoc.querySelectorAll).toHaveBeenCalledWith('.alert.alert-dismissible');
  });

  test('autoStart wires the behaviour onto a global document', () => {
    const doc = freshDoc('<a id="del" href="#x" data-confirm="Sure?">Delete</a>');
    const confirmFn = jest.fn(() => false);

    const fakeGlobal = {
      document: doc,
      confirm: confirmFn,
      setTimeout: window.setTimeout.bind(window)
    };

    PawApp.autoStart(fakeGlobal);

    const event = new window.MouseEvent('click', { bubbles: true, cancelable: true });
    doc.getElementById('del').dispatchEvent(event);

    expect(confirmFn).toHaveBeenCalledWith('Sure?');
    expect(event.defaultPrevented).toBe(true);
  });
});
