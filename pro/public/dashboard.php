<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Require login
Session::requireLogin();

$database = new Database();
$db = $database->getConnection();
    
// Get agency information
$agencyQuery = "SELECT * FROM agencies WHERE id = :id";
$agencyStmt = $db->prepare($agencyQuery);
$agencyStmt->bindParam(':id', $_SESSION['agency_id']);
$agencyStmt->execute();
$agency = $agencyStmt->fetch();

// Get agency resources
$resourcesQuery = "SELECT * FROM resources WHERE agency_id = :agency_id";
$resourcesStmt = $db->prepare($resourcesQuery);
$resourcesStmt->bindParam(':agency_id', $_SESSION['agency_id']);
$resourcesStmt->execute();
$resources = $resourcesStmt->fetchAll();

// Get active alerts
$alertsQuery = "SELECT * FROM alerts WHERE status = 'active' ORDER BY created_at DESC";
$alertsStmt = $db->prepare($alertsQuery);
$alertsStmt->execute();
$alerts = $alertsStmt->fetchAll();

// Get all agencies for the map
$agenciesQuery = "SELECT id, name, agency_type FROM agencies WHERE is_active = 1";
$agenciesStmt = $db->prepare($agenciesQuery);
$agenciesStmt->execute();
$allAgencies = $agenciesStmt->fetchAll();

// Get latest location for each agency
$locationsQuery = "SELECT l.*, a.name, a.agency_type 
                  FROM locations l 
                  INNER JOIN agencies a ON l.agency_id = a.id 
                  WHERE l.timestamp = (
                      SELECT MAX(timestamp) 
                      FROM locations 
                      WHERE agency_id = l.agency_id
                  )";
$locationsStmt = $db->prepare($locationsQuery);
$locationsStmt->execute();
$locations = $locationsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CRCP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        }
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Check for saved theme preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">CRCP</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($agency['name']); ?></span>
                    <a href="logout.php" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">LOGOUT</a>
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5 text-gray-800 dark:text-gray-200 hidden dark:block" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-gray-800 dark:text-gray-200 block dark:hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Map -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 mb-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Interactive Map</h2>
                    <div id="map" class="h-96 rounded-lg"></div>
                </div>

                <!-- Active Alerts -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Active Alerts</h2>
                    <?php if (empty($alerts)): ?>
                        <p class="text-gray-500 dark:text-gray-400">No active alerts</p>
                    <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                            <div class="border-b border-gray-200 dark:border-gray-700 py-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($alert['title']); ?></h3>
                                <p class="text-gray-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($alert['description']); ?></p>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo $alert['severity'] === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                            ($alert['severity'] === 'high' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 
                                            ($alert['severity'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200')); ?>">
                                        <?php echo ucfirst($alert['severity']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Agency Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-[0_0_15px_rgba(239,68,68,0.5)]">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Agency Information</h2>
                    <div class="space-y-2">
                        <p class="text-gray-700 dark:text-gray-300"><span class="font-medium">Type:</span> <?php echo ucfirst($agency['agency_type']); ?></p>
                        <p class="text-gray-700 dark:text-gray-300"><span class="font-medium">Contact:</span> <?php echo htmlspecialchars($agency['contact_number']); ?></p>
                        <p class="text-gray-700 dark:text-gray-300"><span class="font-medium">Location:</span> <?php echo htmlspecialchars($agency['city'] . ', ' . $agency['state'] . ', ' . $agency['country']); ?></p>
                    </div>
                </div>

                <!-- Update Location -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-[0_0_15px_rgba(59,130,246,0.5)]">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Update Location</h2>
                    <form id="locationForm" class="space-y-4 mt-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-[0_0_15px_rgba(59,130,246,0.5)]">
                            <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
                            <input type="text" id="latitude" name="latitude" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-[0_0_8px_rgba(0,0,0,0.2)] dark:shadow-[0_0_8px_rgba(0,0,0,0.4)] focus:border-indigo-500 focus:ring-indigo-500 sm:text-base py-2.5 px-3">
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-[0_0_15px_rgba(239,68,68,0.5)]">
                            <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
                            <input type="text" id="longitude" name="longitude" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-[0_0_8px_rgba(0,0,0,0.2)] dark:shadow-[0_0_8px_rgba(0,0,0,0.4)] focus:border-indigo-500 focus:ring-indigo-500 sm:text-base py-2.5 px-3">
                        </div>
                        <button id="getLocationBtn" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Get Current Location
                        </button>
                        <div id="locationError" class="mt-2 text-sm text-red-600 dark:text-red-400 hidden"></div>
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Update Location
                        </button>
                    </form>
                </div>

                <!-- Resources -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-[0_0_15px_rgba(239,68,68,0.5)]">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Available Resources</h2>
                    <div class="mb-4 space-y-3">
                        <button id="callParamedicBtn" 
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Call Paramedic
                        </button>
                        <button id="callPoliceBtn" 
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Call Police
                        </button>
                        <div id="emergencyStatus" class="mt-2 text-sm text-gray-600 dark:text-gray-400 hidden"></div>
                    </div>
                    <?php if (empty($resources)): ?>
                        <p class="text-gray-500 dark:text-gray-400"></p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($resources as $resource): ?>
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                    <h3 class="font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($resource['name']); ?></h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($resource['description']); ?></p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Quantity: <?php echo $resource['quantity']; ?></span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $resource['status'] === 'available' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                ($resource['status'] === 'in_use' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                                ($resource['status'] === 'maintenance' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 
                                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200')); ?>">
                                            <?php echo ucfirst($resource['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('theme-toggle');
        themeToggle.addEventListener('click', () => {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        });

        // Emergency call functionality
        const callParamedicBtn = document.getElementById('callParamedicBtn');
        const callPoliceBtn = document.getElementById('callPoliceBtn');
        const emergencyStatus = document.getElementById('emergencyStatus');

        function handleEmergencyCall(type) {
            // Show loading state
            const button = type === 'paramedic' ? callParamedicBtn : callPoliceBtn;
            const originalContent = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Calling ${type === 'paramedic' ? 'Paramedic' : 'Police'}...
            `;
            emergencyStatus.classList.remove('hidden');
            emergencyStatus.textContent = `Connecting to ${type === 'paramedic' ? 'medical' : 'police'} emergency services...`;

            // Simulate API call to emergency services
            setTimeout(() => {
                // Reset button state
                button.disabled = false;
                button.innerHTML = originalContent;
                emergencyStatus.textContent = `${type === 'paramedic' ? 'Paramedic' : 'Police'} has been dispatched to your location. ETA: 5-10 minutes.`;
                
                // Show emergency contact information
                alert(`${type === 'paramedic' ? 'Medical' : 'Police'} emergency services have been notified. Please stay on the line if possible.\n\nEmergency Contact: 911\nYour Location: ` + 
                    (navigator.geolocation ? 'Current location' : 'Last updated location'));
            }, 2000);
        }

        callParamedicBtn.addEventListener('click', () => handleEmergencyCall('paramedic'));
        callPoliceBtn.addEventListener('click', () => handleEmergencyCall('police'));

        // Get current location functionality
        const getLocationBtn = document.getElementById('getLocationBtn');
        const locationError = document.getElementById('locationError');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');

        // Handle location update and zoom
        $('#locationForm').on('submit', function(e) {
            e.preventDefault();
            var latitude = $('#latitude').val();
            var longitude = $('#longitude').val();

            // Zoom to the new location
            map.flyTo([latitude, longitude], 15, {
                duration: 4,
                easeLinearity: 0.15
            });

            $.ajax({
                url: 'update_location.php',
                method: 'POST',
                data: {
                    latitude: latitude,
                    longitude: longitude
                },
                success: function(response) {
                    if (response.success) {
                        // Update the marker for the current agency
                        <?php foreach ($locations as $location): ?>
                            <?php if ($location['agency_id'] == $_SESSION['agency_id']): ?>
                                var marker = L.marker([<?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>])
                                    .addTo(map)
                                    .bindPopup('<?php echo htmlspecialchars($location['name']); ?> (<?php echo ucfirst($location['agency_type']); ?>)');
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        // Show success message without reloading
                        alert('Location updated successfully');
                    } else {
                        alert('Error updating location');
                    }
                },
                error: function() {
                    alert('Error updating location');
                }
            });
        });

        // Get current location functionality
        getLocationBtn.addEventListener('click', () => {
            // Show loading state
            getLocationBtn.disabled = true;
            getLocationBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Getting Location...
            `;
            locationError.classList.add('hidden');

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        // Success callback
                        const { latitude, longitude } = position.coords;
                        latitudeInput.value = latitude;
                        longitudeInput.value = longitude;
                        
                        // Zoom to current location
                        map.flyTo([latitude, longitude], 15, {
                            duration: 4,
                            easeLinearity: 0.15
                        });
                        
                        // Reset button state
                        getLocationBtn.disabled = false;
                        getLocationBtn.innerHTML = `
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Get Current Location
                        `;
                    },
                    (error) => {
                        // Error callback
                        let errorMessage = 'Error getting location: ';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'Please allow location access in your browser settings.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Location information is unavailable.';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'The request to get location timed out.';
                                break;
                            default:
                                errorMessage += 'An unknown error occurred.';
                        }
                        locationError.textContent = errorMessage;
                        locationError.classList.remove('hidden');
                        
                        // Reset button state
                        getLocationBtn.disabled = false;
                        getLocationBtn.innerHTML = `
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Get Current Location
                        `;
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            } else {
                // Browser doesn't support Geolocation
                locationError.textContent = 'Geolocation is not supported by your browser.';
                locationError.classList.remove('hidden');
                
                // Reset button state
                getLocationBtn.disabled = false;
                getLocationBtn.innerHTML = `
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Get Current Location
                `;
            }
        });

        // Initialize map
        var map = L.map('map').setView([0, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add markers for all agencies
        <?php foreach ($locations as $location): ?>
            var marker = L.marker([<?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>])
                .addTo(map)
                .bindPopup('<?php echo htmlspecialchars($location['name']); ?> (<?php echo ucfirst($location['agency_type']); ?>)');
        <?php endforeach; ?>
    </script>
</body>
</html> 