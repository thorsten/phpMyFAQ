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

    let s: HTMLElement | null = document.getElementById('expandaSearch');
    let n: HTMLElement | null = document.querySelector('#pmf-top-navbar ul');
    let c: HTMLElement | null = document.querySelector('.searchContainer .bi-close');
    let t: int | null; 

    function showSearch(e: MouseEvent): void {
        if (s && s.querySelector('button') && !s.querySelector('button').disabled) return;
        e.stopPropagation();
        if (n) n.style.display = "none";
        if (s && s.querySelector('input')) s.querySelector('input').value = "";
        if (s) s.classList.remove('searchClosed'); 
        if (s && s.querySelector('button')) s.querySelector('button').disabled = false;
        if (s && s.querySelector('input')) s.querySelector('input').focus();
        document.querySelector('div.searchContainer')!.style.width = "90%";
        redoTimeout();
    }

    function hideSearch(e?: MouseEvent): void {
        if (document.querySelector('ul.autocomplete')) return;
        if (s && s.querySelector('button') && s.querySelector('button').disabled) return;
        if (e) e.stopPropagation();
        if (s) s.classList.add('searchClosed'); 
        if (s && s.querySelector('button')) s.querySelector('button').disabled = true;    
        if (n) n.style.display = "";
        document.querySelector('div.searchContainer')!.style.width = "";
    }

    function checkEsc(e: KeyboardEvent): void {
        if (e.key === "Escape") { 
        if (s && !s.classList.contains('searchClosed')) hideSearch();
        }
    }

    if (s) s.addEventListener('click', showSearch);
    if (c) c.addEventListener('click', hideSearch);
    function redoTimeout() {
        clearTimeout(t);
        t = setTimeout(hideSearch, 3000);
    }

    document.querySelector('.search input').addEventListener("keyup", redoTimeout);
    document.addEventListener('keydown', checkEsc);
};
