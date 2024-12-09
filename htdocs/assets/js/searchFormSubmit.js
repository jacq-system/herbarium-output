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
    const progressBar = document.getElementById('progressBar');
    const targetElement = document.getElementById('results');


    progressBar.classList.remove('hide');
    progressBar.classList.add('show');
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
            progressBar.classList.remove('show');
            progressBar.classList.add('hide');
            let paginator = document.getElementById('recordsPerPage');
            M.FormSelect.init(paginator);
        })
        .catch((error) => {
            console.error('Error:', error);
            targetElement.innerHTML = '<p>Sorry, an error occurred.</p>';
        });

}

