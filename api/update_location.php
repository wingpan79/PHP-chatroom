<?php
define('IN_CHAT', true);
require_once '../config.php';

// get user real IP function
function getRealIP() {
    $ip = null;
    
    // get IP by priority
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // get first real IP from proxy IP chain
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // validate IP format
    if ($ip && filter_var($ip, FILTER_VALIDATE_IP, 
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return $ip;
    }
    
    // if cannot get valid public IP, return REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'];
}

// get location information from multiple IP location APIs
function getLocationFromIP($ip) {
    // try using Pacific IP location API
    try {
        $url = "http://whois.pconline.com.cn/ipJson.jsp?ip={$ip}&json=true";
        $response = file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['city'])) {
                return $data['pro'] . ' ' . $data['city'];
            }
        }
    } catch (Exception $e) {
        error_log("IP location error (pconline): " . $e->getMessage());
    }
    
    // backup: use IPAPI
    try {
        $url = "http://ip-api.com/json/{$ip}?lang=zh-CN";
        $response = file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return $data['regionName'] . ' ' . $data['city'];
            }
        }
    } catch (Exception $e) {
        error_log("IP location error (ip-api): " . $e->getMessage());
    }
    
    // backup: use ipapi.co
    try {
        $url = "https://ipapi.co/{$ip}/json/";
        $response = file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['city'])) {
                return $data['region'] . ' ' . $data['city'];
            }
        }
    } catch (Exception $e) {
        error_log("IP location error (ipapi.co): " . $e->getMessage());
    }
    
    return null;
}

if (isset($_SESSION['user_id'])) {
    try {
        $ip = getRealIP();
        $location = getLocationFromIP($ip);
        
        if ($location) {
            // update user IP
            $stmt = $pdo->prepare("UPDATE users SET ip = ? WHERE id = ?");
            $stmt->execute([$ip, $_SESSION['user_id']]);
            
            // check if location record exists
            $stmt = $pdo->prepare("SELECT id FROM user_locations WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // update existing record
                $stmt = $pdo->prepare("UPDATE user_locations SET city = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$location, $existing['id']]);
            } else {
                // insert new record
                $stmt = $pdo->prepare("INSERT INTO user_locations (user_id, city) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $location]);
            }
            
            echo json_encode([
                'success' => true, 
                'location' => $location,
                'ip' => $ip
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'Cannot get location information',
                'ip' => $ip
            ]);
        }
    } catch (Exception $e) {
        error_log("Location update error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to update location information',
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
} 