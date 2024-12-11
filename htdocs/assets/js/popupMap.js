import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

let mapInstance = null;
export default function popupMap() {

    const modals = document.getElementById('map-modal');
    M.Modal.init(modals);


    document.querySelectorAll('.gps').forEach(element => {
        element.addEventListener('click', function () {
            const gps = this.getAttribute('data-gps');
            if (!gps) {
                return;
            }

            const [lat, lng] = gps.split(',').map(coord => parseFloat(coord.trim()));
            if (isNaN(lat) || isNaN(lng)) {
                return;
            }

            const modalInstance = M.Modal.getInstance(document.getElementById('map-modal'));
            modalInstance.open();


            const mapContainer = document.getElementById('map');
            mapContainer.innerHTML = '';

            if (mapInstance !== null) {
                mapInstance.remove();
            }

            setTimeout(() => {

                mapInstance = L.map('map').setView([lat, lng], 13);

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
        });
    });


}
