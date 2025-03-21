/**
 * WebAuthn authentication
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-07
 */
interface WebAuthnKey {
  challenge: number[];
  allowCredentials: { id: number[] }[];
  [key: string]: any;
}

interface AuthenticatorResponse {
  type: string;
  originalChallenge: number[];
  rawId: number[];
  response: {
    authenticatorData: number[];
    clientData: any;
    clientDataJSONarray: number[];
    signature: number[];
  };
}

type Callback = (success: boolean, info: AuthenticatorResponse | string) => void;

const arrayBufferToArray = (buffer: ArrayBuffer): number[] => Array.from(new Uint8Array(buffer));

export const webauthnAuthenticate = async (webAuthnKey: WebAuthnKey, callback: Callback): Promise<void> => {
  try {
    const { challenge, allowCredentials, ...rest } = webAuthnKey;
    const originalChallenge = challenge;

    const publicKeyCredentialRequestOptions: PublicKeyCredentialRequestOptions = {
      ...rest,
      challenge: new Uint8Array(challenge),
      allowCredentials: allowCredentials.map((cred) => ({
        ...cred,
        id: new Uint8Array(cred.id),
        type: 'public-key',
      })),
    };

    const assertion = (await navigator.credentials.get({
      publicKey: publicKeyCredentialRequestOptions,
    })) as PublicKeyCredential;

    const {
      rawId,
      response: {
        clientDataJSON: clientDataJSONBuffer,
        authenticatorData: authenticatorDataBuffer,
        signature: signatureBuffer,
      },
      type,
    } = assertion as PublicKeyCredential & { response: AuthenticatorAssertionResponse };

    const clientDataJSON = JSON.parse(new TextDecoder().decode(clientDataJSONBuffer));

    const rawIdArray = arrayBufferToArray(rawId);
    const clientDataJSONArray = arrayBufferToArray(clientDataJSONBuffer);
    const authenticatorDataArray = arrayBufferToArray(authenticatorDataBuffer);
    const signatureArray = arrayBufferToArray(signatureBuffer);

    const info: AuthenticatorResponse = {
      type,
      originalChallenge,
      rawId: rawIdArray,
      response: {
        authenticatorData: authenticatorDataArray,
        clientData: clientDataJSON,
        clientDataJSONarray: clientDataJSONArray,
        signature: signatureArray,
      },
    };

    callback(true, info);
  } catch (error: any) {
    const abortErrors = ['AbortError', 'NS_ERROR_ABORT', 'NotAllowedError'];
    if (abortErrors.includes(error.name)) {
      callback(false, 'Authentication aborted by user.');
    } else {
      callback(false, error.toString());
    }
  }
};
