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
    challenge.publicKey.attestation = undefined;
    challenge.publicKey.challenge = new Uint8Array(challenge.publicKey.challenge);
    challenge.publicKey.user.id = new Uint8Array(challenge.publicKey.user.id);

    const newCredential = await navigator.credentials.create({ publicKey: challenge.publicKey });

    const clientDataJSON = JSON.parse(new TextDecoder().decode(new Uint8Array(newCredential.response.clientDataJSON)));

    if (challenge.b64challenge !== clientDataJSON.challenge) {
      return callback(false, 'The challenge is not the same.');
    }

    if (`https://${challenge.publicKey.rp.name}` !== clientDataJSON.origin) {
      return callback(false, 'The origin is not the same.');
    }

    if (!('type' in clientDataJSON) || clientDataJSON.type !== 'webauthn.create') {
      return callback(false, 'The type is not the same.');
    }

    const arrayBufferToArray = (buffer) => Array.from(new Uint8Array(buffer));

    const attestationObject = arrayBufferToArray(newCredential.response.attestationObject);
    const rawId = arrayBufferToArray(newCredential.rawId);

    const info = {
      rawId,
      id: newCredential.id,
      type: newCredential.type,
      response: {
        attestationObject,
        clientDataJSON,
      },
    };

    // Pass the result to the callback
    callback(true, JSON.stringify(info));
  } catch (error) {
    if (['AbortError', 'NS_ERROR_ABORT', 'NotAllowedError'].includes(error.name)) {
      callback(false, 'abort');
    } else {
      callback(false, error.toString());
    }
  }
};
