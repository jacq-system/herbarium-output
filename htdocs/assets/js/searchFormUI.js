import {searchResults} from './searchFormSubmit';

export default function searchFormUI() {

    const element = document.getElementById('institution');
    const form = document.getElementById('searchForm');

    if (!element || !form) {
        return null;
    }
    subsetCollections(element);

    if (form.dataset.prefilled === "1") {
        searchResults(form);
    }
}

function subsetCollections(element) {
    element.addEventListener('change', function (event) {
        const selectedValue = this.value;
        const path = this.dataset.source;
        let url = path;
        if (selectedValue !== '') {
            url = path + `?herbariumID=${encodeURIComponent(selectedValue)}`;
        }
        fetch(url, {
            method: 'GET',
        })
            .then((response) => response.json())
            .then((data) => {
                const secondSelect = document.getElementById('collection');
                secondSelect.innerHTML = '';
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'all subcollections';
                secondSelect.appendChild(defaultOption);

                data.forEach(({id, name}) => {
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = name;
                    secondSelect.appendChild(option);
                });
                M.FormSelect.init(secondSelect);
            })
            .catch((error) => console.error('Error:', error));
    });

}

