<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// PSGC Cloud API Base URL - Free Philippine Geographic Data API
// Documentation: https://psgc.cloud/
define('PSGC_API_BASE', 'https://psgc.cloud/api');

// Load comprehensive Philippine Zip Code Databases (2,500+ entries)
$zipCodesNCR = include(__DIR__ . '/zip_codes_ncr.php');
$zipCodesProvinces = include(__DIR__ . '/zip_codes_provinces.php');
$zipCodesVisMin = include(__DIR__ . '/zip_codes_vismin.php');

// Merge all zip codes into one array for easy lookup
$allZipCodes = array_merge(
    is_array($zipCodesNCR) ? $zipCodesNCR : [],
    is_array($zipCodesProvinces) ? $zipCodesProvinces : [],
    is_array($zipCodesVisMin) ? $zipCodesVisMin : []
);

/**
 * Make HTTP request to PSGC Cloud API
 */
function fetchFromAPI($endpoint) {
    $url = PSGC_API_BASE . $endpoint;
    
    $options = [
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => [
                'Accept: application/json',
                'User-Agent: EvergreenBankApp/1.0'
            ]
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: EvergreenBankApp/1.0'
                ],
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return null;
            }
        } else {
            return null;
        }
    }
    
    return json_decode($response, true);
}

/**
 * Simple file-based caching for API responses
 */
function getCachedOrFetch($key, $callback, $ttl = 86400) {
    $cacheDir = __DIR__ . '/cache';
    $cacheFile = $cacheDir . '/' . md5($key) . '.json';
    
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        if ($cacheData && isset($cacheData['timestamp']) && (time() - $cacheData['timestamp']) < $ttl) {
            return $cacheData['data'];
        }
    }
    
    $data = $callback();
    
    if ($data !== null) {
        $cacheContent = json_encode([
            'timestamp' => time(),
            'data' => $data
        ]);
        @file_put_contents($cacheFile, $cacheContent);
    }
    
    return $data;
}

/**
 * Get zip code for a city or barangay
 * Uses comprehensive database with 2,500+ entries
 */
function getZipCode($cityName, $barangayName = '', $cityCode = '') {
    global $allZipCodes;
    
    // First, try exact barangay match
    if ($barangayName) {
        $cleanBarangay = trim($barangayName);
        if (isset($allZipCodes[$cleanBarangay])) {
            return $allZipCodes[$cleanBarangay];
        }
    }
    
    // Try city name match
    if ($cityName) {
        $cleanCity = trim($cityName);
        
        // Direct match
        if (isset($allZipCodes[$cleanCity])) {
            return $allZipCodes[$cleanCity];
        }
        
        // Try with "City" suffix removed
        $cityNoSuffix = preg_replace('/ City$/i', '', $cleanCity);
        if (isset($allZipCodes[$cityNoSuffix])) {
            return $allZipCodes[$cityNoSuffix];
        }
        
        // Try with "City of" prefix removed
        $cityNoPrefix = preg_replace('/^City of /i', '', $cleanCity);
        if (isset($allZipCodes[$cityNoPrefix])) {
            return $allZipCodes[$cityNoPrefix];
        }
        
        // Try adding "City" suffix
        if (isset($allZipCodes[$cleanCity . ' City'])) {
            return $allZipCodes[$cleanCity . ' City'];
        }
    }
    
    // Try city code match
    if ($cityCode && isset($allZipCodes[$cityCode])) {
        return $allZipCodes[$cityCode];
    }
    
    return '';
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_regions':
        $regions = getCachedOrFetch('regions', function() {
            $data = fetchFromAPI('/regions');
            if ($data) {
                $result = [];
                foreach ($data as $region) {
                    $result[] = [
                        'code' => $region['code'],
                        'name' => $region['name']
                    ];
                }
                usort($result, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                return $result;
            }
            return [];
        });
        echo json_encode($regions ?: []);
        break;

    case 'get_provinces':
        $regionCode = isset($_GET['region_code']) ? $_GET['region_code'] : '';
        
        if ($regionCode) {
            // Get provinces for a specific region
            $provinces = getCachedOrFetch("provinces_{$regionCode}", function() use ($regionCode) {
                $data = fetchFromAPI("/regions/{$regionCode}/provinces");
                if ($data && count($data) > 0) {
                    $result = [];
                    foreach ($data as $province) {
                        $result[] = [
                            'code' => $province['code'],
                            'name' => $province['name']
                        ];
                    }
                    usort($result, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                    return $result;
                }
                return [];
            });
            
            echo json_encode($provinces ?: []);
        } else {
            // No region code provided - return all provinces from all regions
            $allProvinces = getCachedOrFetch("all_provinces", function() {
                // First, get all regions
                $regions = fetchFromAPI('/regions');
                if (!$regions) {
                    return [];
                }
                
                $allProvinces = [];
                $provinceMap = []; // Use map to avoid duplicates
                
                // Fetch provinces from each region
                foreach ($regions as $region) {
                    $regionCode = $region['code'];
                    $provinces = fetchFromAPI("/regions/{$regionCode}/provinces");
                    
                    if ($provinces && count($provinces) > 0) {
                        foreach ($provinces as $province) {
                            $provinceCode = $province['code'];
                            // Avoid duplicates by using code as key
                            if (!isset($provinceMap[$provinceCode])) {
                                $provinceMap[$provinceCode] = [
                                    'code' => $province['code'],
                                    'name' => $province['name']
                                ];
                            }
                        }
                    }
                }
                
                // Convert map to array and sort
                $result = array_values($provinceMap);
                usort($result, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                
                return $result;
            }, 86400 * 7); // Cache for 7 days since this is expensive
            
            echo json_encode($allProvinces ?: []);
        }
        break;

    case 'get_cities':
        $provinceCode = isset($_GET['province_code']) ? $_GET['province_code'] : '';
        $regionCode = isset($_GET['region_code']) ? $_GET['region_code'] : '';
        
        // If province code is provided, get cities from province
        if ($provinceCode) {
            $cities = getCachedOrFetch("cities_{$provinceCode}", function() use ($provinceCode) {
                $data = fetchFromAPI("/provinces/{$provinceCode}/cities-municipalities");
                if ($data) {
                    $result = [];
                    foreach ($data as $city) {
                        $result[] = [
                            'code' => $city['code'],
                            'name' => $city['name']
                        ];
                    }
                    usort($result, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                    return $result;
                }
                return [];
            });
            echo json_encode($cities ?: []);
        }
        // If only region code is provided (for regions without provinces like NCR)
        elseif ($regionCode) {
            $cities = getCachedOrFetch("cities_region_{$regionCode}", function() use ($regionCode) {
                $data = fetchFromAPI("/regions/{$regionCode}/cities-municipalities");
                if ($data) {
                    $result = [];
                    foreach ($data as $city) {
                        $result[] = [
                            'code' => $city['code'],
                            'name' => $city['name']
                        ];
                    }
                    usort($result, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                    return $result;
                }
                return [];
            });
            echo json_encode($cities ?: []);
        }
        else {
            echo json_encode([]);
        }
        break;

    case 'get_barangays':
        $cityCode = isset($_GET['city_code']) ? $_GET['city_code'] : '';
        
        if ($cityCode) {
            $barangays = getCachedOrFetch("barangays_{$cityCode}", function() use ($cityCode) {
                $data = fetchFromAPI("/cities-municipalities/{$cityCode}/barangays");
                if ($data) {
                    $result = [];
                    foreach ($data as $barangay) {
                        $result[] = [
                            'code' => $barangay['code'],
                            'name' => $barangay['name']
                        ];
                    }
                    usort($result, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                    return $result;
                }
                return [];
            });
            echo json_encode($barangays ?: []);
        } else {
            echo json_encode([]);
        }
        break;

    case 'get_zipcode':
        $cityCode = isset($_GET['city_code']) ? $_GET['city_code'] : '';
        $cityName = isset($_GET['city_name']) ? $_GET['city_name'] : '';
        $barangayName = isset($_GET['barangay_name']) ? $_GET['barangay_name'] : '';
        
        $zipcode = getZipCode($cityName, $barangayName, $cityCode);
        
        echo json_encode(['zipcode' => $zipcode]);
        break;

    case 'clear_cache':
        $cacheDir = __DIR__ . '/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*.json');
            foreach ($files as $file) {
                @unlink($file);
            }
            echo json_encode(['success' => true, 'message' => 'Cache cleared']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cache directory not found']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>