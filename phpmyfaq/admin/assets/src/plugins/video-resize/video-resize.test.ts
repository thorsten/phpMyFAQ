import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockPluginsAdd = vi.fn();

vi.mock('jodit', () => ({
  Jodit: {
    plugins: {
      add: mockPluginsAdd,
    },
  },
}));

const createWrapper = (width: number, height: number): HTMLElement => {
  const wrapper = document.createElement('jodit-media');
  wrapper.setAttribute('data-jodit_fake_wrapper', '1');
  Object.defineProperty(wrapper, 'offsetWidth', { configurable: true, get: () => width });
  Object.defineProperty(wrapper, 'offsetHeight', { configurable: true, get: () => height });

  const video = document.createElement('video');
  video.setAttribute('src', 'https://localhost/content/user/images/1_clip.mp4');
  video.setAttribute('width', '640');
  video.style.width = '640px';
  wrapper.appendChild(video);

  return wrapper;
};

describe('applyWrapperSizeToVideo', () => {
  beforeEach(() => {
    vi.resetModules();
  });

  it('should store the wrapper size as width and height attributes of the video', async () => {
    const { applyWrapperSizeToVideo } = await import('./video-resize');
    const wrapper = createWrapper(480, 270);

    applyWrapperSizeToVideo(wrapper);

    const video = wrapper.querySelector('video') as HTMLVideoElement;
    expect(video.getAttribute('width')).toBe('480');
    expect(video.getAttribute('height')).toBe('270');
  });

  it('should remove an inline size that would override the attributes', async () => {
    const { applyWrapperSizeToVideo } = await import('./video-resize');
    const wrapper = createWrapper(480, 270);

    applyWrapperSizeToVideo(wrapper);

    const video = wrapper.querySelector('video') as HTMLVideoElement;
    expect(video.style.width).toBe('');
    expect(video.style.height).toBe('');
  });

  it('should ignore a wrapper without a video', async () => {
    const { applyWrapperSizeToVideo } = await import('./video-resize');
    const wrapper = document.createElement('jodit-media');
    wrapper.appendChild(document.createElement('audio'));

    expect(() => applyWrapperSizeToVideo(wrapper)).not.toThrow();
    expect(wrapper.querySelector('audio')?.hasAttribute('width')).toBe(false);
  });
});

describe('video-resize plugin', () => {
  let editorRoot: HTMLElement;
  let handlers: Map<unknown, Map<string, (event?: Event) => void>>;
  let mockEditor: {
    editor: HTMLElement;
    e: { on: ReturnType<typeof vi.fn> };
    synchronizeValues: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();

    editorRoot = document.createElement('div');
    handlers = new Map();
    // Jodit's on() takes (event, handler) for editor events and (element, event, handler) for elements
    const on = vi.fn((...args: unknown[]) => {
      const [target, event, handler] =
        typeof args[0] === 'string' ? [mockEditor, args[0], args[1]] : [args[0], args[1], args[2]];
      const targetHandlers = handlers.get(target) ?? new Map();
      targetHandlers.set(event as string, handler as (event?: Event) => void);
      handlers.set(target, targetHandlers);
      return mockEditor.e;
    });
    mockEditor = { editor: editorRoot, e: { on }, synchronizeValues: vi.fn() };
  });

  const setUpPlugin = async (): Promise<void> => {
    await import('./video-resize');
    expect(mockPluginsAdd).toHaveBeenCalledWith('videoResize', expect.any(Function));
    const pluginCallback = mockPluginsAdd.mock.calls[0][1] as (editor: typeof mockEditor) => void;
    pluginCallback(mockEditor);
  };

  const pressOn = (target: Element): void => {
    const onMouseDown = handlers.get(mockEditor)?.get('mousedown');
    expect(onMouseDown).toBeDefined();
    onMouseDown?.({ target } as unknown as Event);
  };

  it('should copy the size to the video when a pressed video wrapper is resized', async () => {
    await setUpPlugin();
    const wrapper = createWrapper(480, 270);
    editorRoot.appendChild(wrapper);

    pressOn(wrapper.querySelector('video') as Element);
    handlers.get(wrapper)?.get('changesize')?.();

    expect(wrapper.querySelector('video')?.getAttribute('width')).toBe('480');
    // The resizer synchronizes once the drag ends; syncing on every move would rebuild the content
    expect(mockEditor.synchronizeValues).not.toHaveBeenCalled();
  });

  it('should bind a wrapper only once', async () => {
    await setUpPlugin();
    const wrapper = createWrapper(480, 270);
    editorRoot.appendChild(wrapper);

    pressOn(wrapper);
    pressOn(wrapper);

    const changeSizeBindings = mockEditor.e.on.mock.calls.filter(
      ([target, event]) => target === wrapper && event === 'changesize'
    );
    expect(changeSizeBindings).toHaveLength(1);
  });

  it('should ignore presses outside of a video wrapper', async () => {
    await setUpPlugin();
    const paragraph = document.createElement('p');
    editorRoot.appendChild(paragraph);

    pressOn(paragraph);

    expect(mockEditor.e.on).toHaveBeenCalledTimes(1);
  });
});
