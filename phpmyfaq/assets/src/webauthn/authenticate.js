/**
 * WebAuthn authentication
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
 * @since     2024-09-07
 */

export const webauthnAuthenticate = async (webAuthnKey, callback) => {
  try {
    const privateKey = webAuthnKey;
    const originalChallenge = privateKey.challenge;

    privateKey.challenge = new Uint8Array(privateKey.challenge);

    privateKey.allowCredentials = privateKey.allowCredentials.map((cred) => ({
      ...cred,
      id: new Uint8Array(cred.id),
    }));

    const assertion = await navigator.credentials.get({ publicKey: privateKey });

    const arrayBufferToArray = (buffer) => Array.from(new Uint8Array(buffer));

    const rawId = arrayBufferToArray(assertion.rawId);
    const clientData = JSON.parse(new TextDecoder().decode(assertion.response.clientDataJSON));
    const clientDataJSON = arrayBufferToArray(assertion.response.clientDataJSON);
    const authenticatorData = arrayBufferToArray(assertion.response.authenticatorData);
    const signature = arrayBufferToArray(assertion.response.signature);

    const info = {
      type: assertion.type,
      originalChallenge: originalChallenge,
      rawId: rawId,
      response: {
        authenticatorData: authenticatorData,
        clientData: clientData,
        clientDataJSONarray: clientDataJSON,
        signature: signature,
      },
    };

    callback(true, info);
  } catch (error) {
    if (['AbortError', 'NS_ERROR_ABORT', 'NotAllowedError'].includes(error.name)) {
      callback(false, 'abort');
    } else {
      callback(false, error.toString());
    }
  }
};
