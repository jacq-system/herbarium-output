export default function materializeInit() {

    M.AutoInit();
    updateSelects();
    window.addEventListener('resize', updateSelects);

}

/**
 * Materialize has hard-to-debug problems with Select Options in mobile Chrome (and iOS maybe also).
 * I've tried multiple approaches, but only this worked - on small windows remove Materialize and use native elements
 */
function updateSelects() {
    const isSmallScreen = window.innerWidth < 1000;
    const selects = document.querySelectorAll('select');

    selects.forEach(select => {
        const instance = M.FormSelect.getInstance(select);

        if (isSmallScreen) {
            if (!select.classList.contains('browser-default')) {
                select.classList.add('browser-default');
                if (instance) instance.destroy();
            }
        } else {
            select.classList.remove('browser-default');
            if (!instance) M.FormSelect.init(select);
        }
    });
}
