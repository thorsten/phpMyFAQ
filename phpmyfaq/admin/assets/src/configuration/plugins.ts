/**
 * Plugin management logic for phpMyFAQ admin backend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-07
 */

import { togglePluginStatus, savePluginConfig } from '../api';
import { pushNotification, pushErrorNotification } from '../../../../assets/src/utils';

/**
 * Handles plugin status toggling and configuration modal
 */
export const handlePlugins = (): void => {
    const toggleCheckboxes = document.querySelectorAll<HTMLInputElement>('.plugin-toggle');
    toggleCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', async (event) => {
            const target = event.target as HTMLInputElement;
            const row = target.closest('tr');
            const pluginName = row ? row.getAttribute('data-plugin-name') : null;
            const active = target.checked;

            if (!pluginName) {
                return;
            }

            try {
                const result = await togglePluginStatus(pluginName, active);

                if (!result.success) {
                    target.checked = !active; // Revert
                    pushErrorNotification('Failed to update plugin status: ' + (result.message || 'Unknown error'));
                } else {
                    pushNotification('Plugin status updated successfully.');
                }
            } catch (error) {
                target.checked = !active; // Revert
                console.error('Error toggling plugin:', error);
                pushErrorNotification('An error occurred while updating plugin status.');
            }
        });
    });

    // Configuration Modal
    const configModalEl = document.getElementById('pluginConfigModal');
    if (configModalEl) {
        configModalEl.addEventListener('show.bs.modal', (event: any) => {
            const button = event.relatedTarget as HTMLElement;
            const pluginName = button.getAttribute('data-plugin-name');
            const pluginDescription = button.getAttribute('data-plugin-description');
            const pluginImplementation = button.getAttribute('data-plugin-implementation');
            const configJson = button.getAttribute('data-plugin-config');

            const modalTitle = configModalEl.querySelector('.modal-title');
            const nameInput = configModalEl.querySelector<HTMLInputElement>('#configPluginName');
            const container = configModalEl.querySelector('#configFieldsContainer');
            const descText = configModalEl.querySelector('#pluginDescriptionText');
            const implContainer = configModalEl.querySelector('#pluginImplementationContainer');
            const implCode = configModalEl.querySelector('#pluginImplementationCode');
            const noConfigMsg = configModalEl.querySelector('#pluginNoConfigMsg');
            const saveBtn = document.getElementById('savePluginConfigBtn');

            if (modalTitle && pluginName) {
                modalTitle.textContent = 'Configuration: ' + pluginName;
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
                            const div = document.createElement('div');
                            div.className = 'mb-3';

                            const label = document.createElement('label');
                            label.className = 'form-label';
                            label.textContent = key;
                            label.htmlFor = 'config_' + key;

                            let input: HTMLInputElement;

                            if (typeof value === 'boolean') {
                                div.className = 'form-check form-switch mb-3';
                                input = document.createElement('input');
                                input.type = 'checkbox';
                                input.className = 'form-check-input';
                                input.checked = value;
                                input.value = '1';
                                label.className = 'form-check-label';
                                div.appendChild(input);
                                div.appendChild(label);
                            } else {
                                input = document.createElement('input');
                                input.type = 'text';
                                input.className = 'form-control';
                                input.value = String(value);
                                div.appendChild(label);
                                div.appendChild(input);
                            }

                            input.id = 'config_' + key;
                            input.setAttribute('name', 'config[' + key + ']');

                            if (container) {
                                container.appendChild(div);
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
                    } else {
                        configData[key] = input.value;
                    }
                }
            });

            if (!pluginName) return;

            try {
                const result = await savePluginConfig(pluginName, configData);

                if (result.success) {
                    window.location.reload();
                } else {
                    pushErrorNotification('Failed to save configuration: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving config:', error);
                pushErrorNotification('An error occurred while saving configuration.');
            }
        });
    }
};
