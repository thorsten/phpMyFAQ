import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleFileFilter } from './media.browser';

describe('handleFileFilter', () => {
  let postMessageMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';

    postMessageMock = vi.fn();
    Object.defineProperty(window, 'parent', {
      value: { postMessage: postMessageMock },
      writable: true,
    });
  });

  describe('filter input behavior', () => {
    it('should not throw when filter input does not exist', () => {
      document.body.innerHTML = `
        <div class="mce-file">image.png</div>
      `;

      expect(() => handleFileFilter()).not.toThrow();
    });

    it('should filter file divs based on input text', () => {
      document.body.innerHTML = `
        <input id="filter" type="text" />
        <div class="mce-file">photo.png</div>
        <div class="mce-file">document.pdf</div>
        <div class="mce-file">archive.zip</div>
      `;

      handleFileFilter();

      const filterInput = document.getElementById('filter') as HTMLInputElement;
      filterInput.value = 'photo';
      filterInput.dispatchEvent(new Event('keyup'));

      const fileDivs = document.querySelectorAll('div.mce-file') as NodeListOf<HTMLElement>;
      expect(fileDivs[0].style.display).toBe('block');
      expect(fileDivs[1].style.display).toBe('none');
      expect(fileDivs[2].style.display).toBe('none');
    });

    it('should show all file divs when filter input is empty', () => {
      document.body.innerHTML = `
        <input id="filter" type="text" />
        <div class="mce-file">photo.png</div>
        <div class="mce-file">document.pdf</div>
      `;

      handleFileFilter();

      const filterInput = document.getElementById('filter') as HTMLInputElement;
      filterInput.value = '';
      filterInput.dispatchEvent(new Event('keyup'));

      const fileDivs = document.querySelectorAll('div.mce-file') as NodeListOf<HTMLElement>;
      expect(fileDivs[0].style.display).toBe('block');
      expect(fileDivs[1].style.display).toBe('block');
    });

    it('should filter with case-insensitive matching', () => {
      document.body.innerHTML = `
        <input id="filter" type="text" />
        <div class="mce-file">Photo.PNG</div>
        <div class="mce-file">DOCUMENT.pdf</div>
        <div class="mce-file">archive.ZIP</div>
      `;

      handleFileFilter();

      const filterInput = document.getElementById('filter') as HTMLInputElement;
      filterInput.value = 'photo';
      filterInput.dispatchEvent(new Event('keyup'));

      const fileDivs = document.querySelectorAll('div.mce-file') as NodeListOf<HTMLElement>;
      expect(fileDivs[0].style.display).toBe('block');
      expect(fileDivs[1].style.display).toBe('none');
      expect(fileDivs[2].style.display).toBe('none');
    });

    it('should handle case-insensitive filter with uppercase input', () => {
      document.body.innerHTML = `
        <input id="filter" type="text" />
        <div class="mce-file">photo.png</div>
        <div class="mce-file">document.pdf</div>
      `;

      handleFileFilter();

      const filterInput = document.getElementById('filter') as HTMLInputElement;
      filterInput.value = 'PHOTO';
      filterInput.dispatchEvent(new Event('keyup'));

      const fileDivs = document.querySelectorAll('div.mce-file') as NodeListOf<HTMLElement>;
      expect(fileDivs[0].style.display).toBe('block');
      expect(fileDivs[1].style.display).toBe('none');
    });
  });

  describe('click behavior for file selection', () => {
    it('should post message to parent when a .mce-file div with data-src is clicked', () => {
      document.body.innerHTML = `
        <input id="filter" type="text" />
        <div class="mce-file" data-src="/uploads/image.png">image.png</div>
      `;

      handleFileFilter();

      const fileDiv = document.querySelector('div.mce-file') as HTMLElement;
      fileDiv.click();

      expect(postMessageMock).toHaveBeenCalledWith(
        {
          mceAction: 'phpMyFAQMediaBrowserAction',
          url: '/uploads/image.png',
        },
        '*'
      );
    });

    it('should not post message when a .mce-file div without data-src is clicked', () => {
      document.body.innerHTML = `
        <input id="filter" type="text" />
        <div class="mce-file">image.png</div>
      `;

      handleFileFilter();

      const fileDiv = document.querySelector('div.mce-file') as HTMLElement;
      fileDiv.click();

      expect(postMessageMock).not.toHaveBeenCalled();
    });

    it('should not post message when a non-.mce-file element is clicked', () => {
      document.body.innerHTML = `
        <input id="filter" type="text" />
        <div class="mce-file" data-src="/uploads/image.png">image.png</div>
        <div class="other-element" data-src="/uploads/other.png">other</div>
      `;

      handleFileFilter();

      const otherDiv = document.querySelector('.other-element') as HTMLElement;
      otherDiv.click();

      expect(postMessageMock).not.toHaveBeenCalled();
    });

    it('should post message with correct src for different file divs', () => {
      document.body.innerHTML = `
        <input id="filter" type="text" />
        <div class="mce-file" data-src="/uploads/first.png">first.png</div>
        <div class="mce-file" data-src="/uploads/second.jpg">second.jpg</div>
      `;

      handleFileFilter();

      const secondFileDiv = document.querySelectorAll('div.mce-file')[1] as HTMLElement;
      secondFileDiv.click();

      expect(postMessageMock).toHaveBeenCalledWith(
        {
          mceAction: 'phpMyFAQMediaBrowserAction',
          url: '/uploads/second.jpg',
        },
        '*'
      );
    });

    it('should still register click listener even when filter input does not exist', () => {
      document.body.innerHTML = `
        <div class="mce-file" data-src="/uploads/image.png">image.png</div>
      `;

      handleFileFilter();

      const fileDiv = document.querySelector('div.mce-file') as HTMLElement;
      fileDiv.click();

      expect(postMessageMock).toHaveBeenCalledWith(
        {
          mceAction: 'phpMyFAQMediaBrowserAction',
          url: '/uploads/image.png',
        },
        '*'
      );
    });
  });
});
