import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

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

        const mapInstance = L.map('map').setView([lat, lng], 13);

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
export async function dynamicRecords(){
    let container = document.getElementById('dynamic-references');
    if (!container) {
        return;
    }

    //GGBN
    try {
        const response = await fetch("https://www.ggbn.org/ggbn_portal/api/search?getCounts&guid=https://w.jacq.org/W19920010523");
        if (!response.ok) {
            throw new Error(`Error: ${response.status}`);
        }

        const data = await response.json();

        if (data.nbSamples === 0 || data.ggbnId == null) {
            return;
        }else {
            let ggbnElement = document.getElementById('dynamic-ggbn');
            ggbnElement.innerHTML = data.ggbnId;
            container.classList.remove("hidden");
        }



    } catch (error) {
    }
}
