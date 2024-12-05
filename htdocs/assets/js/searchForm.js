 export default function searchForm() {

     const element = document.getElementById('institutions');

     if (!element) {
         return null;
     }
    element.addEventListener('change', function (event) {
        const selectedValue = this.value;
        const path = this.dataset.source;

        fetch(path + `?herbariumID=${encodeURIComponent(selectedValue)}`, {
            method: 'GET',
        })
            .then((response) => response.json())
            .then((data) => {
                const secondSelect = document.getElementById('collections');
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

