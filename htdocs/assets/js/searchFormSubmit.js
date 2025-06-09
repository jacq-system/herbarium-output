import paginationInit from "./searchFormPagination";
import popupMap from "./popupMap";
import searchFormSortingInit from "./searchFormSorting";

export function searchFormSubmit() {

    const form = document.getElementById('searchForm');

    if (!form) {
        return null;
    }
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        searchResults(form);
    });


}

export function searchResults(form) {
    const targetElement = document.getElementById('results');

    showProgressBar();

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then((html) => {
            targetElement.innerHTML = html;
            let paginator = document.getElementById('recordsPerPage');
            if (paginator) {
                M.FormSelect.init(paginator);
                paginationInit();
                searchFormSortingInit();
                popupMap();
            }
            hideProgressBar();
        })
        .catch((error) => {
            console.error('Error:', error);
            targetElement.innerHTML = '<p>Sorry, an error occurred.</p>';
        });

}

export function showProgressBar() {
    const progressBar = document.getElementById('progressBar');
    progressBar.classList.remove('hide');
    progressBar.classList.add('show');
}

export function hideProgressBar() {
    const progressBar = document.getElementById('progressBar');
    progressBar.classList.remove('show');
    progressBar.classList.add('hide');


    let elems = document.querySelectorAll('.tooltipped');
    M.Tooltip.init(elems);

}
