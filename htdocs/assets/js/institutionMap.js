import L from 'leaflet';
import 'leaflet.markercluster/dist/leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet/dist/leaflet.css';

// fix pro produkci
delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
    iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
    iconUrl: require('leaflet/dist/images/marker-icon.png'),
    shadowUrl: require('leaflet/dist/images/marker-shadow.png'),
});

let mapInstance = null;
export default function institutionMap() {
    const mapContainer = document.getElementById('map-institutions');
    if (!mapContainer) {
        return null;
    }

    mapInstance = L.map('map-institutions').setView([50.0755, 14.4378], 3);

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
