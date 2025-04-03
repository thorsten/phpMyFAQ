/**
 * Expandasearch functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-11-23
 */

export const handleExpandaSearch = (): void => {

    let searchContainer: HTMLElement | null = document.getElementById('expandaSearch');
    let menuList: HTMLElement | null = document.querySelector('#pmf-top-navbar ul');
    let closeBtn: HTMLElement | null = document.querySelector('.searchContainer .bi-close');
    let timeOut: int | null; 

    function showSearch(event: MouseEvent): void {
        if (searchContainer && searchContainer.querySelector('button') && !searchContainer.querySelector('button').disabled) return;
        event.stopPropagation();
        if (menuList) menuList.style.display = "none";
        if (searchContainer && searchContainer.querySelector('input')) searchContainer.querySelector('input').value = "";
        if (searchContainer) searchContainer.classList.remove('searchClosed'); 
        if (searchContainer && searchContainer.querySelector('button')) searchContainer.querySelector('button').disabled = false;
        if (searchContainer && searchContainer.querySelector('input')) searchContainer.querySelector('input').focus();
        document.querySelector('div.searchContainer')!.style.width = "75%";
        redoTimeout();
    }

    function hideSearch(event?: MouseEvent): void {
        if (document.querySelector('ul.autocomplete')) return;
        if (searchContainer && searchContainer.querySelector('button') && searchContainer.querySelector('button').disabled) return;
        if (event) event.stopPropagation();
        if (searchContainer) searchContainer.classList.add('searchClosed'); 
        if (searchContainer && searchContainer.querySelector('button')) searchContainer.querySelector('button').disabled = true;    
        if (menuList) menuList.style.display = "";
        document.querySelector('div.searchContainer')!.style.width = "";
    }

    function checkEsc(event: KeyboardEvent): void {
        if (event.key === "Escape") { 
        if (searchContainer && !searchContainer.classList.contains('searchClosed')) hideSearch();
        }
    }

    function redoTimeout() {
        clearTimeout(timeOut);
        timeOut = setTimeout(hideSearch, 3000);
    }

    if (searchContainer) searchContainer.addEventListener('click', showSearch);
    if (closeBtn) closeBtn.addEventListener('click', hideSearch);

    document.querySelector('.search input').addEventListener("keyup", redoTimeout);
    document.addEventListener('keydown', checkEsc);
};
