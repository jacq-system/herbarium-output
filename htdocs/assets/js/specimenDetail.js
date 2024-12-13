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
