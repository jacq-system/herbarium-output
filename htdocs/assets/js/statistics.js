export default function statistics() {
    document.getElementById('statisticsForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(this);

        const params = new URLSearchParams();
        formData.forEach((value, key) => {
            params.append(key, value);
        });
        const url = this.action + '?' + params.toString();

        fetch(url, {
            method: 'GET',
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Sorry, en error occurred: ' + response.statusText);
                }
                return response.text();
            })
            .then(html => {
                document.getElementById('statistics_result').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Sorry, en error occurred.');
            });

    });
}
