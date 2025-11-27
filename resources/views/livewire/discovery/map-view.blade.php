<div class="relative h-screen">
    {{-- Map Container --}}
    <div id="map" class="w-full h-full"></div>

    {{-- Floating Controls --}}
    <div class="absolute top-6 left-6 right-6 z-10">
        <div class="relative p-4 glass-card lg:rounded-xl max-w-md">
            <div class="flex items-center gap-4">
                {{-- Content Type Filter --}}
                <select wire:model.live="contentType" class="flex-1 px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition">
                    <option value="all">All Content</option>
                    <option value="posts">Posts Only</option>
                    <option value="events">Events Only</option>
                </select>

                {{-- Center on Me Button --}}
                <button onclick="centerOnUser()" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-sm font-semibold hover:scale-105 transition-all whitespace-nowrap">
                    📍 Center on Me
                </button>
            </div>
        </div>
    </div>

    {{-- Preview Card (shown when marker is clicked) --}}
    <div id="preview-card" class="absolute bottom-6 left-6 right-6 z-10 hidden">
        <div class="relative p-6 glass-card lg:rounded-xl max-w-md mx-auto">
            <button onclick="closePreview()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div id="preview-content"></div>
        </div>
    </div>

    {{-- Google Maps Script --}}
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initMap" async defer></script>

    <script>
        let map;
        let markers = [];
        let userMarker;
        const userLat = {{ $userLat }};
        const userLng = {{ $userLng }};
        const markersData = @json($markers);

        function initMap() {
            // Dark theme map styles
            const darkMapStyles = [
                { elementType: "geometry", stylers: [{ color: "#1e293b" }] },
                { elementType: "labels.text.stroke", stylers: [{ color: "#0f172a" }] },
                { elementType: "labels.text.fill", stylers: [{ color: "#94a3b8" }] }
            ];

            // Initialize map
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: userLat, lng: userLng },
                zoom: 13,
                styles: darkMapStyles,
                disableDefaultUI: false,
                zoomControl: true,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });

            // Add user location marker
            userMarker = new google.maps.Marker({
                position: { lat: userLat, lng: userLng },
                map: map,
                title: "You are here",
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: "#06b6d4",
                    fillOpacity: 1,
                    strokeColor: "#ffffff",
                    strokeWeight: 3,
                },
            });

            // Add markers for posts and events
            addMarkers();
        }

        function addMarkers() {
            // Clear existing markers
            markers.forEach(marker => marker.setMap(null));
            markers = [];

            // Add new markers
            markersData.forEach(data => {
                const marker = new google.maps.Marker({
                    position: { lat: data.lat, lng: data.lng },
                    map: map,
                    title: data.title,
                    icon: getMarkerIcon(data.type, data.converted),
                });

                marker.addListener('click', () => showPreview(data));
                markers.push(marker);
            });
        }

        function getMarkerIcon(type, converted) {
            if (type === 'post') {
                return {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 12,
                    fillColor: "#ec4899",
                    fillOpacity: 0.9,
                    strokeColor: "#a855f7",
                    strokeWeight: 2,
                };
            } else if (converted) {
                return {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 14,
                    fillColor: "#a855f7",
                    fillOpacity: 0.9,
                    strokeColor: "#fbbf24",
                    strokeWeight: 3,
                };
            } else {
                return {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 12,
                    fillColor: "#06b6d4",
                    fillOpacity: 0.9,
                    strokeColor: "#3b82f6",
                    strokeWeight: 2,
                };
            }
        }

        function showPreview(data) {
            const previewCard = document.getElementById('preview-card');
            const previewContent = document.getElementById('preview-content');

            if (data.type === 'post') {
                previewContent.innerHTML = `
                    <h3 class="text-xl font-bold mb-2 text-white">${data.title}</h3>
                    <p class="text-gray-400 text-sm mb-4">${data.description || ''}</p>
                    <div class="flex items-center gap-4 text-sm text-gray-400 mb-4">
                        <span>📍 ${data.location_name}</span>
                        <span>⏱️ ${data.expires_at}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-pink-500/30 border border-pink-500/50 rounded-lg text-sm">
                            ${data.reaction_count} reactions
                        </span>
                    </div>
                `;
            } else {
                previewContent.innerHTML = `
                    <h3 class="text-xl font-bold mb-2 text-white">${data.title}</h3>
                    <p class="text-gray-400 text-sm mb-4">${data.description || ''}</p>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <div class="text-xs text-gray-400">When</div>
                            <div class="text-sm font-semibold text-white">${data.start_time}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400">Price</div>
                            <div class="text-sm font-semibold text-white">${data.price}</div>
                        </div>
                    </div>
                    ${data.converted ? '<span class="px-3 py-1 bg-purple-500/30 border border-purple-500/50 rounded-full text-xs font-bold text-purple-300">⭐ Converted from Post</span>' : ''}
                `;
            }

            previewCard.classList.remove('hidden');
        }

        function closePreview() {
            document.getElementById('preview-card').classList.add('hidden');
        }

        function centerOnUser() {
            map.setCenter({ lat: userLat, lng: userLng });
            map.setZoom(13);
        }
    </script>
</div>
