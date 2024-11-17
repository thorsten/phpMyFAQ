import { handleReloadCaptcha } from './captcha';

global.fetch = vi.fn(() =>
  Promise.resolve({
    status: 200,
  })
);

document.body.innerHTML = `
  <button id="reloadButton" data-action="refresh">Reload</button>
  <input id="captcha" />
  <img id="captchaImage" src="" />
`;

describe('handleReloadCaptcha', () => {
  it('should reload captcha image and clear captcha input on button click', async () => {
    const reloadButton = document.getElementById('reloadButton');
    const captcha = document.getElementById('captcha');
    const date = Math.floor(new Date().getTime() / 1000);

    handleReloadCaptcha(reloadButton);

    reloadButton.click();

    await Promise.resolve();

    expect(fetch).toHaveBeenCalledWith('api/captcha', {
      body: '{"action":"refresh","timestamp":' + date + '}',
      cache: 'no-cache',
      method: 'POST',
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
    expect(captcha.value).toBe('');
  });
});
