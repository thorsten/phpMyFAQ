/**
 * Plugin management logic for phpMyFAQ admin backend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-07
 */

import { togglePluginStatus, savePluginConfig } from '../api';
import { addElement, pushNotification, pushErrorNotification, TranslationService } from '../../../../assets/src/utils';

/**
 * Handles plugin status toggling and configuration modal
 */
export const handlePlugins = async (): Promise<void> => {
    const Translator = new TranslationService();
    await Translator.loadTranslations(document.documentElement.lang);

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const toggleCheckboxes = document.querySelectorAll<HTMLInputElement>('.plugin-toggle');
    toggleCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', async (event) => {
            const target = event.target as HTMLInputElement;
            const row = target.closest('tr');
            const pluginName = row ? row.getAttribute('data-plugin-name') : null;
            const active = target.checked;
            const csrfToken = getCsrfToken();

            if (!pluginName) {
                return;
            }

            try {
                const result = await togglePluginStatus(pluginName, active, csrfToken);

                if (!result.success) {
                    target.checked = !active; // Revert
                    pushErrorNotification(
                        Translator.translate('msgPluginStatusError') + ' ' + (result.message || Translator.translate('msgUnknownError')),
                    );
                } else {
                    pushNotification(Translator.translate('msgPluginStatusSuccess'));
                }
            } catch (error: any) {
                target.checked = !active; // Revert
                console.error('Error toggling plugin:', error);
                pushErrorNotification(error.message || Translator.translate('msgUnknownError'));
            }
        });
    });

    // Configuration Modal
    const pluginConfigModal = document.getElementById('pluginConfigModal');
    if (pluginConfigModal) {
        pluginConfigModal.addEventListener('show.bs.modal', (event: any) => {
            const button = event.relatedTarget as HTMLElement;
            const pluginName = button.getAttribute('data-plugin-name');
            const pluginDescription = button.getAttribute('data-plugin-description');
            const pluginImplementation = button.getAttribute('data-plugin-implementation');
            const configJson = button.getAttribute('data-plugin-config');

            const modalTitle = pluginConfigModal.querySelector('.modal-title');
            const nameInput = pluginConfigModal.querySelector<HTMLInputElement>('#configPluginName');
            const container = pluginConfigModal.querySelector('#configFieldsContainer');
            const descText = pluginConfigModal.querySelector('#pluginDescriptionText');
            const implContainer = pluginConfigModal.querySelector('#pluginImplementationContainer');
            const implCode = pluginConfigModal.querySelector('#pluginImplementationCode');
            const noConfigMsg = pluginConfigModal.querySelector('#pluginNoConfigMsg');
            const saveBtn = document.getElementById('savePluginConfigBtn');

            if (modalTitle && pluginName) {
                modalTitle.textContent = Translator.translate('msgConfig') + ': ' + pluginName;
            }
            if (nameInput && pluginName) {
                nameInput.value = pluginName;
            }
            if (descText) {
                descText.textContent = pluginDescription || '-';
            }

            if (implContainer && implCode) {
                if (pluginImplementation) {
                    implCode.textContent = pluginImplementation;
                    implContainer.classList.remove('d-none');
                } else {
                    implContainer.classList.add('d-none');
                }
            }

            if (container) {
                container.innerHTML = ''; // Clear previous fields
            }
            if (noConfigMsg) noConfigMsg.classList.add('d-none');
            if (saveBtn) saveBtn.style.display = 'none';

            let hasConfig = false;

            if (configJson) {
                try {
                    const configData = JSON.parse(configJson);

                    if (configData && typeof configData === 'object' && Object.keys(configData).length > 0) {
                        hasConfig = true;
                        if (saveBtn) saveBtn.style.display = 'block';

                        Object.keys(configData).forEach((key) => {
                            const value = configData[key];
                            let input: HTMLElement;

                            if (typeof value === 'boolean') {
                                input = addElement('div', { className: 'form-check form-switch mb-3' }, [
                                    addElement('input', {
                                        type: 'checkbox',
                                        className: 'form-check-input',
                                        checked: value,
                                        value: '1',
                                        id: 'config_' + key,
                                        name: 'config[' + key + ']',
                                    }),
                                    addElement('label', {
                                        className: 'form-check-label',
                                        textContent: key,
                                        htmlFor: 'config_' + key,
                                    }),
                                ]);
                            } else {
                                const type = (typeof value === 'number' || !isNaN(Number(value)) && String(value).trim() !== '') ? 'number' :
                                    (key.toLowerCase().includes('email')) ? 'email' :
                                        (key.toLowerCase().includes('date')) ? 'date' : 'text';

                                const props: Record<string, any> = {
                                    className: 'form-control',
                                    value: String(value),
                                    id: 'config_' + key,
                                    name: 'config[' + key + ']',
                                };

                                if (String(value).length > 50 && type === 'text') {
                                    input = addElement('div', { className: 'mb-3' }, [
                                        addElement('label', { className: 'form-label', textContent: key, htmlFor: 'config_' + key }),
                                        addElement('textarea', { ...props, rows: 3 }),
                                    ]);
                                } else {
                                    input = addElement('div', { className: 'mb-3' }, [
                                        addElement('label', { className: 'form-label', textContent: key, htmlFor: 'config_' + key }),
                                        addElement('input', { ...props, type }),
                                    ]);
                                }
                            }

                            if (container) {
                                container.appendChild(input);
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error parsing config:', e);
                }
            }

            if (!hasConfig && noConfigMsg) {
                noConfigMsg.classList.remove('d-none');
            }
        });
    }

    // Save Configuration
    const saveBtn = document.getElementById('savePluginConfigBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            const form = document.getElementById('pluginConfigForm') as HTMLFormElement;
            const container = document.getElementById('configFieldsContainer');
            if (!form || !container) return;

            const nameInput = form.querySelector<HTMLInputElement>('input[name="name"]');
            const pluginName = nameInput?.value;
            const csrfToken = getCsrfToken();
            const configData: Record<string, any> = {};

            const inputs = container.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
                'input, select, textarea',
            );
            inputs.forEach((input) => {
                const nameAttr = input.getAttribute('name');
                if (nameAttr && nameAttr.startsWith('config[')) {
                    const key = nameAttr.substring(7, nameAttr.length - 1);
                    if (input instanceof HTMLInputElement && input.type === 'checkbox') {
                        configData[key] = input.checked;
                    } else if (input instanceof HTMLInputElement && input.type === 'number') {
                        configData[key] = input.value.includes('.') ? parseFloat(input.value) : parseInt(input.value, 10);
                        if (isNaN(configData[key])) {
                            configData[key] = 0;
                        }
                    } else {
                        configData[key] = input.value;
                    }
                }
            });

            if (!pluginName) return;

            try {
                const result = await savePluginConfig(pluginName, configData, csrfToken);

                if (result.success) {
                    pushNotification(Translator.translate('msgPluginConfigSuccess'));
                    window.location.reload();
                } else {
                    pushErrorNotification(
                        Translator.translate('msgPluginConfigError') + ' ' + (result.message || Translator.translate('msgUnknownError')),
                    );
                }
            } catch (error: any) {
                console.error('Error saving config:', error);
                pushErrorNotification(error.message || Translator.translate('msgUnknownError'));
            }
        });
    }
};
