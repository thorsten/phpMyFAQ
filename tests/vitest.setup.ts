// Node 24+ exposes a stub `localStorage` global that shadows the jsdom-provided
// one. Vitest's jsdom environment skips keys that already exist on `globalThis`,
// so we restore the working jsdom Storage objects after the environment is set up.
const jsdom = (globalThis as unknown as { jsdom?: { window: Window } }).jsdom;
if (jsdom) {
  Object.defineProperty(globalThis, 'localStorage', {
    configurable: true,
    writable: true,
    value: jsdom.window.localStorage,
  });
  Object.defineProperty(globalThis, 'sessionStorage', {
    configurable: true,
    writable: true,
    value: jsdom.window.sessionStorage,
  });
}
