(function (){
let s = document.getElementById('expandaSearch');
let n = document.querySelector('#pmf-top-navbar ul');
let c = document.querySelector('.searchContainer .bi-search');
function showSearch (e) {
    e.stopPropagation();
    n.style.display = "none";
    s.querySelector('input').value = "";
    s.classList.remove('searchClosed'); 
    s.querySelector('button').disabled = false;
    s.querySelector('input').focus();
    document.querySelector('div.searchContainer').style.width = "90%";
}

function hideSearch (e) {
    if (e) e.stopPropagation();
    s.classList.add('searchClosed'); 
    s.querySelector('button').disabled = true;    
    n.style.display = "";
    document.querySelector('div.searchContainer').style.width = "";
}
function checkEsc(e) {
    if (e.key === "Escape") { // escape key maps to keycode `27`
       if (!s.classList.contains('searchClosed')) hideSearch();
    }
}
s.addEventListener('click', showSearch);
c.addEventListener('click', hideSearch);
document.querySelector('div.searchContainer').addEventListener('keydown', checkEsc);
})();