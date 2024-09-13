/**
 * WebAuthn registration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-08
 */

export const webauthnRegister = async (challenge, callback) => {
  try {
    const { publicKey, b64challenge } = challenge;

    const publicKeyCredentialCreationOptions = {
      ...publicKey,
      attestation: undefined, // Not requesting attestation
      challenge: new Uint8Array(publicKey.challenge), // Convert challenge to Uint8Array
      user: {
        ...publicKey.user,
        id: new Uint8Array(publicKey.user.id), // Convert user ID to Uint8Array
      },
    };

    const credential = await navigator.credentials.create({
      publicKey: publicKeyCredentialCreationOptions,
    });

    const decodeClientDataJSON = (buffer) => {
      const decodedString = new TextDecoder().decode(buffer);
      return JSON.parse(decodedString);
    };

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

    const arrayBufferToArray = (buffer) => Array.from(new Uint8Array(buffer));

    const attestationObject = arrayBufferToArray(credential.response.attestationObject);
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
  } catch (error) {
    const abortErrors = ['AbortError', 'NS_ERROR_ABORT', 'NotAllowedError'];
    if (abortErrors.includes(error.name)) {
      callback(false, 'Registration aborted by user.');
    } else {
      callback(false, error.toString());
    }
  }
};
