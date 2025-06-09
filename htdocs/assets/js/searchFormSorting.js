import {searchResults} from './searchFormSubmit';
export default function searchFormSortingInit() {
    const form = document.getElementById('searchForm');

    const sortableColumns = document.querySelectorAll('.sortable');
    sortableColumns.forEach(item => {
        handleChangeSort(item, form);
    });

}

function handleChangeSort(element, form) {
    element.addEventListener('click', function (event) {
        const column = this.dataset.sort;
        const path = this.dataset.path;

        fetch(path + `?feature=sort&value=${encodeURIComponent(column)}`, {
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



