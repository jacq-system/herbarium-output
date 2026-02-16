import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet.fullscreen';

export default function specimenMap() {

    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        return;
    }
    const gps = mapContainer.getAttribute('data-gps');
    if (!gps) {
        return;
    }

    const [lat, lng] = gps.split(',').map(coord => parseFloat(coord.trim()));
    if (isNaN(lat) || isNaN(lng)) {
        return;
    }

    setTimeout(() => {

        const mapInstance = L.map('map',
            { fullscreenControl: true}
        ).setView([lat, lng], 13);

        L.Icon.Default.mergeOptions({
            iconUrl: markerIcon,
            shadowUrl: markerShadow,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
        }).addTo(mapInstance);

        L.marker([lat, lng]).addTo(mapInstance)
            .bindPopup(`Location: ${lat}, ${lng}`)
            .openPopup();
    }, 100);

}

export async function dynamicReferences() {
    let container = document.getElementById('dynamic-references');
    if (container && container.dataset.pid) {
        await checkGGBN(container);
    }

}
async function checkGGBN(containerElement){
    const response = await fetch("https://www.ggbn.org/ggbn_portal/api/search?getCounts&guid=" + containerElement.dataset.pid);
    if (!response.ok) {
        // throw new Error(`Error: ${response.status}`);
        console.log(`Error: ${response.status}`);
    }
    const data = await response.json();

    if (data.nbSamples === 0 || data.ggbnId == null) {
    } else {
        buildGGBNLinkElement(Object.keys(data.ggbnId)[0]);
        containerElement.classList.remove("hide");
    }
}
function buildGGBNLinkElement(id) {
    let ggbnElement = document.getElementById('dynamic-ggbn');

    let link = document.createElement('a');
    link.href = "https://www.ggbn.org/ggbn_portal/search/record?ggbnId=" + id;
    link.target = "_blank";

    let img = document.createElement('img');
    img.src = "/logo/services/GGBN.png";
    img.alt = "GGBN";
    img.title = "GGBN";
    img.style.width = "20px";
    img.style.height = "auto";

    link.appendChild(img);

    ggbnElement.innerHTML = "";
    ggbnElement.appendChild(link);
}
