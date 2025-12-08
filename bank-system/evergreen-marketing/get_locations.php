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
            // Get ALL provinces (for signup form) - returns province names only
            $allProvinces = getCachedOrFetch("all_provinces", function() {
                $data = fetchFromAPI("/provinces");
                if ($data) {
                    $result = [];
                    foreach ($data as $province) {
                        $result[] = $province['name'];
                    }
                    // Add Metro Manila as a special entry
                    $result[] = 'Metro Manila';
                    sort($result);
                    return array_unique($result);
                }
                return [];
            });
            
            echo json_encode($allProvinces ?: []);
        }
        break;

    case 'get_cities':
        $provinceCode = isset($_GET['province_code']) ? $_GET['province_code'] : '';
        $regionCode = isset($_GET['region_code']) ? $_GET['region_code'] : '';
        $provinceName = isset($_GET['province']) ? $_GET['province'] : '';
        
        // If province NAME is provided (for signup form - returns city names only)
        if ($provinceName) {
            // Handle Metro Manila specially
            if (strtolower($provinceName) === 'metro manila') {
                $cities = getCachedOrFetch("cities_metro_manila_names", function() {
                    // NCR region code is typically 130000000
                    $data = fetchFromAPI("/regions/130000000/cities-municipalities");
                    if ($data) {
                        $result = [];
                        foreach ($data as $city) {
                            $result[] = $city['name'];
                        }
                        sort($result);
                        return $result;
                    }
                    return [];
                });
                echo json_encode($cities ?: []);
            } else {
                // Find province code by name first, then get cities
                $cities = getCachedOrFetch("cities_by_name_" . md5($provinceName), function() use ($provinceName) {
                    // Get all provinces to find the code
                    $provinces = fetchFromAPI("/provinces");
                    $provinceCode = null;
                    
                    if ($provinces) {
                        foreach ($provinces as $province) {
                            if (strcasecmp($province['name'], $provinceName) === 0) {
                                $provinceCode = $province['code'];
                                break;
                            }
                        }
                    }
                    
                    if ($provinceCode) {
                        $data = fetchFromAPI("/provinces/{$provinceCode}/cities-municipalities");
                        if ($data) {
                            $result = [];
                            foreach ($data as $city) {
                                $result[] = $city['name'];
                            }
                            sort($result);
                            return $result;
                        }
                    }
                    return [];
                });
                echo json_encode($cities ?: []);
            }
        }
        // If province code is provided, get cities from province
        elseif ($provinceCode) {
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

    case 'find_region_by_location':
        // Find region code based on province name or city name
        // Used by evergreen_form.php to auto-select region from signup data
        $provinceName = isset($_GET['province']) ? trim($_GET['province']) : '';
        $cityName = isset($_GET['city']) ? trim($_GET['city']) : '';
        
        $result = ['region_code' => '', 'region_name' => '', 'province_code' => '', 'city_code' => ''];
        
        // Metro Manila cities mapping
        $metroManilaCities = [
            'manila', 'quezon city', 'makati', 'makati city', 'pasig', 'pasig city',
            'taguig', 'taguig city', 'mandaluyong', 'mandaluyong city', 'pasay', 'pasay city',
            'paranaque', 'parañaque', 'paranaque city', 'parañaque city',
            'muntinlupa', 'muntinlupa city', 'las pinas', 'las piñas', 'las pinas city', 'las piñas city',
            'marikina', 'marikina city', 'san juan', 'san juan city',
            'caloocan', 'caloocan city', 'malabon', 'malabon city',
            'navotas', 'navotas city', 'valenzuela', 'valenzuela city', 'pateros'
        ];
        
        // Check if it's Metro Manila
        $isMetroManila = false;
        if (strtolower($provinceName) === 'metro manila' || 
            strtolower($provinceName) === 'ncr' ||
            strtolower($provinceName) === 'national capital region') {
            $isMetroManila = true;
        }
        
        // Check if city is in Metro Manila
        if (!$isMetroManila && $cityName) {
            $cityLower = strtolower($cityName);
            foreach ($metroManilaCities as $mmCity) {
                if (strpos($cityLower, $mmCity) !== false || strpos($mmCity, $cityLower) !== false) {
                    $isMetroManila = true;
                    break;
                }
            }
        }
        
        if ($isMetroManila) {
            $result['region_code'] = '130000000';
            $result['region_name'] = 'National Capital Region (NCR)';
            $result['province_code'] = 'METRO_MANILA';
            
            // Try to find city code
            if ($cityName) {
                $cities = getCachedOrFetch("cities_metro_manila_codes", function() {
                    $data = fetchFromAPI("/regions/130000000/cities-municipalities");
                    if ($data) {
                        $cityMap = [];
                        foreach ($data as $city) {
                            $cityMap[strtolower($city['name'])] = $city['code'];
                        }
                        return $cityMap;
                    }
                    return [];
                });
                
                $cityLower = strtolower($cityName);
                if (isset($cities[$cityLower])) {
                    $result['city_code'] = $cities[$cityLower];
                } else {
                    // Try partial match
                    foreach ($cities as $name => $code) {
                        if (strpos($name, $cityLower) !== false || strpos($cityLower, $name) !== false) {
                            $result['city_code'] = $code;
                            break;
                        }
                    }
                }
            }
        } else if ($provinceName) {
            // Find province and its region
            $provinceData = getCachedOrFetch("province_region_map", function() {
                $regions = fetchFromAPI("/regions");
                $map = [];
                
                if ($regions) {
                    foreach ($regions as $region) {
                        $provinces = fetchFromAPI("/regions/{$region['code']}/provinces");
                        if ($provinces) {
                            foreach ($provinces as $province) {
                                $map[strtolower($province['name'])] = [
                                    'province_code' => $province['code'],
                                    'region_code' => $region['code'],
                                    'region_name' => $region['name']
                                ];
                            }
                        }
                    }
                }
                return $map;
            }, 604800); // Cache for 1 week
            
            $provinceLower = strtolower($provinceName);
            if (isset($provinceData[$provinceLower])) {
                $result['region_code'] = $provinceData[$provinceLower]['region_code'];
                $result['region_name'] = $provinceData[$provinceLower]['region_name'];
                $result['province_code'] = $provinceData[$provinceLower]['province_code'];
            }
        }
        
        echo json_encode($result);
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
