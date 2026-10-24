import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockPluginsAdd = vi.fn();

vi.mock('jodit', () => ({
  Jodit: {
    plugins: {
      add: mockPluginsAdd,
    },
  },
}));

const createVideo = (width: number, height: number): HTMLVideoElement => {
  const video = document.createElement('video');
  video.setAttribute('src', 'https://localhost/content/user/images/1_clip.mp4');
  video.setAttribute('width', '640');
  video.setAttribute('height', '360');
  // The resizer sizes the video through its style
  video.style.width = `${width}px`;
  video.style.height = `${height}px`;
  Object.defineProperty(video, 'offsetWidth', { configurable: true, get: () => width });
  Object.defineProperty(video, 'offsetHeight', { configurable: true, get: () => height });

  return video;
};

const wrap = (video: HTMLVideoElement): HTMLElement => {
  const wrapper = document.createElement('jodit-media');
  wrapper.setAttribute('data-jodit_fake_wrapper', '1');
  wrapper.appendChild(video);

  return wrapper;
};

describe('applyVideoSize', () => {
  beforeEach(() => {
    vi.resetModules();
  });

  it('should store the rendered size as width and height attributes', async () => {
    const { applyVideoSize } = await import('./video-resize');
    const video = createVideo(480, 270);

    applyVideoSize(video);

    expect(video.getAttribute('width')).toBe('480');
    expect(video.getAttribute('height')).toBe('270');
  });

  it('should remove the inline size, which the sanitizer drops on save', async () => {
    const { applyVideoSize } = await import('./video-resize');
    const video = createVideo(480, 270);

    applyVideoSize(video);

    expect(video.style.width).toBe('');
    expect(video.style.height).toBe('');
    expect(video.hasAttribute('style')).toBe(false);
  });

  it('should keep other inline styles of the video', async () => {
    const { applyVideoSize } = await import('./video-resize');
    const video = createVideo(480, 270);
    video.style.borderRadius = '4px';

    applyVideoSize(video);

    expect(video.getAttribute('style')).toBe('border-radius: 4px;');
  });
});

describe('video-resize plugin', () => {
  let handlers: Map<unknown, Map<string, (event?: Event) => void>>;
  let mockEditor: {
    editor: HTMLElement;
    e: { on: ReturnType<typeof vi.fn> };
    synchronizeValues: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();

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
    mockEditor = { editor: document.createElement('div'), e: { on }, synchronizeValues: vi.fn() };
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

  it('should store the size when a pressed video is resized', async () => {
    await setUpPlugin();
    const video = createVideo(480, 270);
    mockEditor.editor.appendChild(video);

    pressOn(video);
    handlers.get(video)?.get('changesize')?.();

    expect(video.getAttribute('width')).toBe('480');
    expect(video.getAttribute('height')).toBe('270');
    // The resizer synchronizes once the drag ends; syncing on every move would rebuild the content
    expect(mockEditor.synchronizeValues).not.toHaveBeenCalled();
  });

  it('should bind the video when its media wrapper is pressed', async () => {
    await setUpPlugin();
    const video = createVideo(480, 270);
    mockEditor.editor.appendChild(wrap(video));

    pressOn(video.parentElement as Element);
    handlers.get(video)?.get('changesize')?.();

    expect(video.getAttribute('width')).toBe('480');
  });

  it('should bind a video only once', async () => {
    await setUpPlugin();
    const video = createVideo(480, 270);
    mockEditor.editor.appendChild(video);

    pressOn(video);
    pressOn(video);

    const changeSizeBindings = mockEditor.e.on.mock.calls.filter(
      ([target, event]) => target === video && event === 'changesize'
    );
    expect(changeSizeBindings).toHaveLength(1);
  });

  it('should ignore presses outside of a video', async () => {
    await setUpPlugin();
    const paragraph = document.createElement('p');
    mockEditor.editor.appendChild(paragraph);

    pressOn(paragraph);

    expect(mockEditor.e.on).toHaveBeenCalledTimes(1);
  });
});
