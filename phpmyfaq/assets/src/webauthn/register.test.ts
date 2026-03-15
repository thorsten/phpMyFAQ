import { describe, it, expect, vi, beforeEach } from 'vitest';
import { webauthnRegister } from './register';
import { Callback } from '../interfaces';

// Helper to create an ArrayBuffer from an array of numbers
const toArrayBuffer = (arr: number[]): ArrayBuffer => {
  const buffer = new ArrayBuffer(arr.length);
  const view = new Uint8Array(buffer);
  arr.forEach((val, i) => (view[i] = val));
  return buffer;
};

// Helper to encode clientDataJSON
const encodeClientDataJSON = (data: object): ArrayBuffer => {
  return new TextEncoder().encode(JSON.stringify(data)).buffer as ArrayBuffer;
};

describe('webauthnRegister', () => {
  let callback: Callback;

  beforeEach(() => {
    vi.clearAllMocks();
    callback = vi.fn();
  });

  it('should call callback with success and registration info on valid credential', async () => {
    const b64challenge = 'dGVzdC1jaGFsbGVuZ2U';

    const clientDataJSON = encodeClientDataJSON({
      type: 'webauthn.create',
      challenge: b64challenge,
      origin: window.location.origin,
    });

    const attestationObject = toArrayBuffer([1, 2, 3, 4]);
    const rawId = toArrayBuffer([10, 20, 30]);

    const mockCredential = {
      id: 'credential-id-123',
      type: 'public-key',
      rawId,
      response: {
        clientDataJSON,
        attestationObject,
      },
    };

    Object.defineProperty(navigator, 'credentials', {
      value: { create: vi.fn().mockResolvedValue(mockCredential) },
      writable: true,
      configurable: true,
    });

    const challenge = {
      publicKey: {
        challenge: toArrayBuffer([116, 101, 115, 116]),
        user: {
          id: toArrayBuffer([1, 2, 3]),
          name: 'testuser',
          displayName: 'Test User',
        },
        rp: { name: 'phpMyFAQ' },
        pubKeyCredParams: [{ type: 'public-key' as const, alg: -7 }],
      },
      b64challenge,
    };

    await webauthnRegister(challenge, callback);

    expect(callback).toHaveBeenCalledWith(true, expect.any(String));

    const registrationInfo = JSON.parse((callback as ReturnType<typeof vi.fn>).mock.calls[0][1] as string);
    expect(registrationInfo.id).toBe('credential-id-123');
    expect(registrationInfo.type).toBe('public-key');
    expect(registrationInfo.rawId).toEqual([10, 20, 30]);
    expect(registrationInfo.response.attestationObject).toEqual([1, 2, 3, 4]);
    expect(registrationInfo.response.clientDataJSON.type).toBe('webauthn.create');
  });

  it('should call callback with error when challenge does not match', async () => {
    const clientDataJSON = encodeClientDataJSON({
      type: 'webauthn.create',
      challenge: 'wrong-challenge',
      origin: window.location.origin,
    });

    const mockCredential = {
      id: 'cred-id',
      type: 'public-key',
      rawId: toArrayBuffer([1]),
      response: {
        clientDataJSON,
        attestationObject: toArrayBuffer([1]),
      },
    };

    Object.defineProperty(navigator, 'credentials', {
      value: { create: vi.fn().mockResolvedValue(mockCredential) },
      writable: true,
      configurable: true,
    });

    const challenge = {
      publicKey: {
        challenge: toArrayBuffer([1]),
        user: { id: toArrayBuffer([1]), name: 'user', displayName: 'User' },
        rp: { name: 'test' },
        pubKeyCredParams: [{ type: 'public-key' as const, alg: -7 }],
      },
      b64challenge: 'expected-challenge',
    };

    await webauthnRegister(challenge, callback);

    expect(callback).toHaveBeenCalledWith(false, 'The challenge does not match.');
  });

  it('should call callback with error when origin does not match', async () => {
    const b64challenge = 'test-challenge';

    const clientDataJSON = encodeClientDataJSON({
      type: 'webauthn.create',
      challenge: b64challenge,
      origin: 'https://evil.example.com',
    });

    const mockCredential = {
      id: 'cred-id',
      type: 'public-key',
      rawId: toArrayBuffer([1]),
      response: {
        clientDataJSON,
        attestationObject: toArrayBuffer([1]),
      },
    };

    Object.defineProperty(navigator, 'credentials', {
      value: { create: vi.fn().mockResolvedValue(mockCredential) },
      writable: true,
      configurable: true,
    });

    const challenge = {
      publicKey: {
        challenge: toArrayBuffer([1]),
        user: { id: toArrayBuffer([1]), name: 'user', displayName: 'User' },
        rp: { name: 'test' },
        pubKeyCredParams: [{ type: 'public-key' as const, alg: -7 }],
      },
      b64challenge,
    };

    await webauthnRegister(challenge, callback);

    expect(callback).toHaveBeenCalledWith(false, 'The origin does not match.');
  });

  it('should call callback with error when clientDataJSON type is incorrect', async () => {
    const b64challenge = 'test-challenge';

    const clientDataJSON = encodeClientDataJSON({
      type: 'webauthn.get',
      challenge: b64challenge,
      origin: window.location.origin,
    });

    const mockCredential = {
      id: 'cred-id',
      type: 'public-key',
      rawId: toArrayBuffer([1]),
      response: {
        clientDataJSON,
        attestationObject: toArrayBuffer([1]),
      },
    };

    Object.defineProperty(navigator, 'credentials', {
      value: { create: vi.fn().mockResolvedValue(mockCredential) },
      writable: true,
      configurable: true,
    });

    const challenge = {
      publicKey: {
        challenge: toArrayBuffer([1]),
        user: { id: toArrayBuffer([1]), name: 'user', displayName: 'User' },
        rp: { name: 'test' },
        pubKeyCredParams: [{ type: 'public-key' as const, alg: -7 }],
      },
      b64challenge,
    };

    await webauthnRegister(challenge, callback);

    expect(callback).toHaveBeenCalledWith(false, 'Incorrect clientDataJSON type.');
  });

  it('should call callback with abort message on AbortError', async () => {
    const abortError = new Error('User cancelled');
    abortError.name = 'AbortError';

    Object.defineProperty(navigator, 'credentials', {
      value: { create: vi.fn().mockRejectedValue(abortError) },
      writable: true,
      configurable: true,
    });

    const challenge = {
      publicKey: {
        challenge: toArrayBuffer([1]),
        user: { id: toArrayBuffer([1]), name: 'user', displayName: 'User' },
        rp: { name: 'test' },
        pubKeyCredParams: [{ type: 'public-key' as const, alg: -7 }],
      },
      b64challenge: 'challenge',
    };

    await webauthnRegister(challenge, callback);

    expect(callback).toHaveBeenCalledWith(false, 'Registration aborted by user.');
  });

  it('should call callback with abort message on NotAllowedError', async () => {
    const notAllowedError = new Error('Not allowed');
    notAllowedError.name = 'NotAllowedError';

    Object.defineProperty(navigator, 'credentials', {
      value: { create: vi.fn().mockRejectedValue(notAllowedError) },
      writable: true,
      configurable: true,
    });

    const challenge = {
      publicKey: {
        challenge: toArrayBuffer([1]),
        user: { id: toArrayBuffer([1]), name: 'user', displayName: 'User' },
        rp: { name: 'test' },
        pubKeyCredParams: [{ type: 'public-key' as const, alg: -7 }],
      },
      b64challenge: 'challenge',
    };

    await webauthnRegister(challenge, callback);

    expect(callback).toHaveBeenCalledWith(false, 'Registration aborted by user.');
  });

  it('should call callback with error string on generic Error', async () => {
    const genericError = new Error('Something broke');

    Object.defineProperty(navigator, 'credentials', {
      value: { create: vi.fn().mockRejectedValue(genericError) },
      writable: true,
      configurable: true,
    });

    const challenge = {
      publicKey: {
        challenge: toArrayBuffer([1]),
        user: { id: toArrayBuffer([1]), name: 'user', displayName: 'User' },
        rp: { name: 'test' },
        pubKeyCredParams: [{ type: 'public-key' as const, alg: -7 }],
      },
      b64challenge: 'challenge',
    };

    await webauthnRegister(challenge, callback);

    expect(callback).toHaveBeenCalledWith(false, 'Error: Something broke');
  });

  it('should call callback with stringified value on non-Error throw', async () => {
    Object.defineProperty(navigator, 'credentials', {
      value: { create: vi.fn().mockRejectedValue('string error') },
      writable: true,
      configurable: true,
    });

    const challenge = {
      publicKey: {
        challenge: toArrayBuffer([1]),
        user: { id: toArrayBuffer([1]), name: 'user', displayName: 'User' },
        rp: { name: 'test' },
        pubKeyCredParams: [{ type: 'public-key' as const, alg: -7 }],
      },
      b64challenge: 'challenge',
    };

    await webauthnRegister(challenge, callback);

    expect(callback).toHaveBeenCalledWith(false, 'string error');
  });
});
