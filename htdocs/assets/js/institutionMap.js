import L from 'leaflet';
import 'leaflet.markercluster/dist/leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet/dist/leaflet.css';

let mapInstance = null;
export default function institutionMap() {

        const mapContainer = document.getElementById('map-institutions');

        mapInstance = L.map('map-institutions').setView([50.0755, 14.4378], 2);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
        }).addTo(mapInstance);

        const institutions = JSON.parse(mapContainer.dataset.institutions);
    institutions.forEach(inst => {
        L.marker([inst.lat, inst.lon])
            .addTo(mapInstance)
            .bindPopup(
                `<b>${inst.name}</b><br>` +
                `<a href="${inst.link}" target="_blank">${inst.link}</a>`
            );
    });


}
