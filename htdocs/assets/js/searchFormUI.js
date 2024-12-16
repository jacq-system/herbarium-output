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

        fetch(path + `?herbariumID=${encodeURIComponent(selectedValue)}`, {
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

                for (const [value, label] of Object.entries(data)) {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    secondSelect.appendChild(option);
                }
                M.FormSelect.init(secondSelect);
            })
            .catch((error) => console.error('Error:', error));
    });

}

