(function () {
    function createFoodMarker() {
        return L.divIcon({
            className: 'food-map-marker',
            html: '<div class="food-marker-pin"><span aria-hidden="true">🍽</span></div>',
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -36]
        });
    }

    function openDirections(destinationLat, destinationLng) {
        var destination = destinationLat + ',' + destinationLng;
        var fallbackUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(destination) + '&travelmode=driving';

        if (!navigator.geolocation) {
            window.open(fallbackUrl, '_blank', 'noopener');
            return;
        }

        navigator.geolocation.getCurrentPosition(function (position) {
            var origin = position.coords.latitude + ',' + position.coords.longitude;
            var url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) + '&destination=' + encodeURIComponent(destination) + '&travelmode=driving';
            window.open(url, '_blank', 'noopener');
        }, function () {
            window.open(fallbackUrl, '_blank', 'noopener');
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        });
    }

    function initMap(mapEl) {
        if (typeof L === 'undefined' || !mapEl) return;

        var lat = parseFloat(mapEl.dataset.lat || '');
        var lng = parseFloat(mapEl.dataset.lng || '');
        if (Number.isNaN(lat) || Number.isNaN(lng)) return;

        var address = mapEl.dataset.address || 'Pickup location';
        var label = mapEl.dataset.label || '';
        var popup = '<strong>' + address + '</strong>';
        if (label) popup += '<br><span>' + label + '</span>';

        var map = L.map(mapEl, { zoomControl: true, dragging: true, scrollWheelZoom: false })
            .setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng], { icon: createFoodMarker() })
            .addTo(map)
            .bindPopup(popup);

        var btn = document.getElementById(mapEl.dataset.directionsBtnId || '');
        if (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                openDirections(lat, lng);
            });
        }
    }

    var maps = document.querySelectorAll('[data-food-location-map="1"]');
    maps.forEach(initMap);
})();
