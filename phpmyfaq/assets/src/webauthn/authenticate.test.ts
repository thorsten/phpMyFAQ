import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../utils', () => ({
  TranslationService: vi.fn(function () {
    return {
      loadTranslations: vi.fn().mockResolvedValue(undefined),
      translate: vi.fn().mockImplementation((key: string) => `translated_${key}`),
    };
  }),
}));

import { TranslationService } from '../utils';
import { webauthnAuthenticate } from './authenticate';

const toBuffer = (value: number[] | string): ArrayBuffer => {
  if (typeof value === 'string') {
    return new TextEncoder().encode(value).buffer as ArrayBuffer;
  }

  return new Uint8Array(value).buffer as ArrayBuffer;
};

describe('webauthnAuthenticate', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.documentElement.lang = 'en';

    Object.defineProperty(navigator, 'credentials', {
      configurable: true,
      value: {
        get: vi.fn(),
      },
    });
  });

  it('converts the assertion response and calls the callback with success', async () => {
    const clientData = {
      type: 'webauthn.get',
      challenge: 'challenge',
      origin: 'https://localhost',
    };

    vi.mocked(navigator.credentials.get).mockResolvedValue({
      type: 'public-key',
      rawId: toBuffer([1, 2, 3]),
      response: {
        clientDataJSON: toBuffer(JSON.stringify(clientData)),
        authenticatorData: toBuffer([4, 5, 6]),
        signature: toBuffer([7, 8, 9]),
      },
    } as unknown as Credential);

    const callback = vi.fn();

    await webauthnAuthenticate(
      {
        challenge: [11, 12, 13],
        allowCredentials: [{ id: [21, 22, 23] }],
        timeout: 60_000,
      },
      callback
    );

    expect(navigator.credentials.get).toHaveBeenCalledWith({
      publicKey: expect.objectContaining({
        challenge: new Uint8Array([11, 12, 13]),
        allowCredentials: [
          expect.objectContaining({
            id: new Uint8Array([21, 22, 23]),
            type: 'public-key',
          }),
        ],
      }),
    });

    expect(callback).toHaveBeenCalledWith(
      true,
      expect.objectContaining({
        type: 'public-key',
        originalChallenge: [11, 12, 13],
        rawId: [1, 2, 3],
        response: expect.objectContaining({
          authenticatorData: [4, 5, 6],
          clientData,
          clientDataJSONarray: Array.from(new Uint8Array(toBuffer(JSON.stringify(clientData)))),
          signature: [7, 8, 9],
        }),
      })
    );
  });

  it('returns a translated message when authentication is aborted', async () => {
    vi.mocked(navigator.credentials.get).mockRejectedValue(
      Object.assign(new Error('Aborted by user'), { name: 'NotAllowedError' })
    );

    const callback = vi.fn();

    await webauthnAuthenticate(
      {
        challenge: [1],
        allowCredentials: [{ id: [2] }],
      },
      callback
    );

    const translationService = vi.mocked(TranslationService).mock.results[0]?.value as {
      loadTranslations: ReturnType<typeof vi.fn>;
      translate: ReturnType<typeof vi.fn>;
    };

    expect(translationService.loadTranslations).toHaveBeenCalledWith('en');
    expect(translationService.translate).toHaveBeenCalledWith('msgAuthenticationAborted');
    expect(callback).toHaveBeenCalledWith(false, 'translated_msgAuthenticationAborted');
  });

  it('returns the error string for non-abort failures', async () => {
    vi.mocked(navigator.credentials.get).mockRejectedValue(new Error('Something went wrong'));

    const callback = vi.fn();

    await webauthnAuthenticate(
      {
        challenge: [1],
        allowCredentials: [{ id: [2] }],
      },
      callback
    );

    expect(callback).toHaveBeenCalledWith(false, 'Error: Something went wrong');
  });
});
