(function () {
    var mapEl = document.getElementById('listingMapPicker');
    if (!mapEl || typeof L === 'undefined') return;

    var latInput = document.getElementById('pickup_latitude');
    var lngInput = document.getElementById('pickup_longitude');
    var useCurrentBtn = document.getElementById('use-current-location');
    var feedbackEl = document.getElementById('location-feedback');

    var defaultLat = 24.8607;
    var defaultLng = 67.0011;
    var defaultZoom = 12;

    var startLat = parseFloat(mapEl.dataset.lat || '');
    var startLng = parseFloat(mapEl.dataset.lng || '');
    var hasSaved = !Number.isNaN(startLat) && !Number.isNaN(startLng);

    var map = L.map(mapEl).setView(hasSaved ? [startLat, startLng] : [defaultLat, defaultLng], hasSaved ? 15 : defaultZoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var marker = null;

    function setMessage(text, kind) {
        if (!feedbackEl) return;
        feedbackEl.textContent = text;
        feedbackEl.classList.remove('is-error', 'is-success');
        if (kind === 'error') feedbackEl.classList.add('is-error');
        if (kind === 'success') feedbackEl.classList.add('is-success');
    }

    function updateLatLng(lat, lng) {
        if (!latInput || !lngInput) return;
        latInput.value = Number(lat).toFixed(8);
        lngInput.value = Number(lng).toFixed(8);
        setMessage('Location pinned at ' + latInput.value + ', ' + lngInput.value, 'success');
    }

    function placeMarker(lat, lng, recenter) {
        if (!marker) {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function (event) {
                var p = event.target.getLatLng();
                updateLatLng(p.lat, p.lng);
            });
        } else {
            marker.setLatLng([lat, lng]);
        }
        if (recenter) map.setView([lat, lng], 16);
        updateLatLng(lat, lng);
    }

    map.on('click', function (event) {
        placeMarker(event.latlng.lat, event.latlng.lng, false);
    });

    if (hasSaved) {
        placeMarker(startLat, startLng, false);
    } else {
        setMessage('Click on the map to pin exact pickup location.', null);
    }

    if (useCurrentBtn) {
        useCurrentBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                setMessage('Geolocation is not supported by your browser.', 'error');
                return;
            }
            setMessage('Fetching your current location...', null);
            navigator.geolocation.getCurrentPosition(function (position) {
                placeMarker(position.coords.latitude, position.coords.longitude, true);
            }, function () {
                setMessage('Unable to access your location. Please pin it manually on the map.', 'error');
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            });
        });
    }
})();
