import {hideProgressBar, searchFormSubmit, searchResults, showProgressBar} from './searchFormSubmit';
export default function paginationInit() {
    const form = document.getElementById('searchForm');

    const pageItems = document.querySelectorAll('li.page');
    pageItems.forEach(item => {
       handleChangePage(item, form);
    });

    const recordsPerPage = document.getElementById('recordsPerPage');
    if (recordsPerPage) {
        handleRecordsPerPage(recordsPerPage, form);
    }
}

function handleChangePage(element, form) {
    element.addEventListener('click', function (event) {
        const newPage = this.dataset.number;
        const path = this.dataset.path;
        fetch(path + `?feature=page&value=${encodeURIComponent(newPage)}`, {
            method: 'GET',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            })
            .then((data) => {
                searchResults(form);
            })
            .catch((error) => console.error('Error:', error));
    });

}

function handleRecordsPerPage(element, form) {
    element.addEventListener('change', function (event) {
        const selectedValue = this.value;
        const path = this.dataset.path;
        fetch(path + `?feature=recordsPerPage&value=${encodeURIComponent(selectedValue)}`, {
            method: 'GET',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            })
            .then((data) => {
                searchResults(form);
            })
            .catch((error) => console.error('Error:', error));
    });
}

