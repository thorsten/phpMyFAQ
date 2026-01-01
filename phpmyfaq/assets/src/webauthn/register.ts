/**
 * WebAuthn registration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-08
 */

import { Callback } from '../interfaces';

const arrayBufferToArray = (buffer: ArrayBuffer): number[] => Array.from(new Uint8Array(buffer));

const decodeClientDataJSON = (buffer: ArrayBuffer) => {
  const decodedString = new TextDecoder().decode(buffer);
  return JSON.parse(decodedString);
};

export const webauthnRegister = async (
  challenge: { publicKey: PublicKeyCredentialCreationOptions; b64challenge: string },
  callback: Callback
): Promise<void> => {
  try {
    const { publicKey, b64challenge } = challenge;

    const publicKeyCredentialCreationOptions: PublicKeyCredentialCreationOptions = {
      ...publicKey,
      attestation: undefined, // Not requesting attestation
      challenge: new Uint8Array(publicKey.challenge as ArrayBuffer), // Convert challenge to Uint8Array
      user: {
        ...publicKey.user,
        id: new Uint8Array(publicKey.user.id as ArrayBuffer), // Convert user ID to Uint8Array
      },
    };

    const credential = (await navigator.credentials.create({
      publicKey: publicKeyCredentialCreationOptions,
    })) as PublicKeyCredential;

    const clientDataJSON = decodeClientDataJSON(credential.response.clientDataJSON);

    if (b64challenge !== clientDataJSON.challenge) {
      return callback(false, 'The challenge does not match.');
    }

    const expectedOrigin = window.location.origin;
    if (expectedOrigin !== clientDataJSON.origin) {
      return callback(false, 'The origin does not match.');
    }

    if (clientDataJSON.type !== 'webauthn.create') {
      return callback(false, 'Incorrect clientDataJSON type.');
    }

    const attestationObject = arrayBufferToArray(
      (credential.response as AuthenticatorAttestationResponse).attestationObject
    );
    const rawId = arrayBufferToArray(credential.rawId);

    const registrationInfo = {
      id: credential.id,
      rawId,
      type: credential.type,
      response: {
        attestationObject,
        clientDataJSON,
      },
    };

    callback(true, JSON.stringify(registrationInfo));
  } catch (error: unknown) {
    const abortErrors = ['AbortError', 'NS_ERROR_ABORT', 'NotAllowedError'];
    if (error instanceof Error && abortErrors.includes(error.name)) {
      callback(false, 'Registration aborted by user.');
    } else {
      callback(false, error instanceof Error ? error.toString() : String(error));
    }
  }
};
