import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import omnivore from "leaflet-omnivore/leaflet-omnivore";

let mapInstance = null;
export default function popupMap() {

    const modals = document.getElementById('map-modal');
    M.Modal.init(modals);

//map for individual specimens
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

    //map for all specimens
    const element = document.getElementById('specimensMapTrigger');
    element.addEventListener('click', function () {

        const modalInstance = M.Modal.getInstance(document.getElementById('map-modal'));
        modalInstance.open();

        const mapContainer = document.getElementById('map');
        mapContainer.innerHTML = '<p style="text-align: center; font-size: 18px; padding-top: 200px;">Loading...</p>';

        if (mapInstance !== null) {
            mapInstance.remove();
        }


        mapInstance = L.map('map');

        L.Icon.Default.mergeOptions({
            iconUrl: markerIcon,
            shadowUrl: markerShadow,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
        }).addTo(mapInstance);

        let kmlUrl = document.getElementById('specimensMapTrigger').dataset.kmlsource;
        omnivore.kml(kmlUrl)
            .on('ready', function () {
                // mapContainer.innerHTML = '';
                mapInstance.invalidateSize();
                mapInstance.fitBounds(this.getBounds());
                this.eachLayer(function (layer) {
                    if (layer.feature && layer.feature.properties) {
                        const { name, description } = layer.feature.properties;
                        layer.bindPopup(`
                    <b>${name || 'specimen'}</b><br>
                    ${description || ''}
                `);
                    }
                });
            })
            .addTo(mapInstance)
            .on('error', function () {
                mapContainer.innerHTML = '<p style="text-align: center; color: red; font-size: 18px; padding-top: 200px;">Error during data loading.</p>';
            });
    });
}
