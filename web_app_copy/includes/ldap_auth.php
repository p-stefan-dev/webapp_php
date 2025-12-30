<?php
// includes/ldap_auth.php

/**
 * Autentifică și returnează datele utilizatorului din AD
 * @return array|false - Returnează datele userului sau FALSE dacă eșuează
 */
function login_and_get_ad_user($input_user_or_email, $password) {
    
    // 1. Încărcăm configurația
    $config = require __DIR__ . '/../config/ldap_config.php';
    
    if (empty($input_user_or_email) || empty($password)) return false;

    $host = $config['server']['host'];
    $domain = $config['server']['domain'];
    $port = $config['server']['port'];
    $base_dn = $config['server']['base_dn'];

    // PHP 8.2 Connection URI
    $ldap_uri = "ldap://{$host}:{$port}";
    
    try {
        $ldap_conn = ldap_connect($ldap_uri);
        if (!$ldap_conn) return false;

        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

        // --- A. Determinăm formatul de login pentru BIND ---
        // AD preferă user@domeniu pentru login
        if (strpos($input_user_or_email, '@') === false) {
            $ldap_user_principal = $input_user_or_email . "@" . $domain;
            $search_filter = "(sAMAccountName=$input_user_or_email)"; // Căutăm după username
        } else {
            $ldap_user_principal = $input_user_or_email;
            $search_filter = "(mail=$input_user_or_email)"; // Căutăm după email
        }

        // --- B. Încercăm Autentificarea (BIND) ---
        $bind = @ldap_bind($ldap_conn, $ldap_user_principal, $password);

        if (!$bind) {
            return false; // Parolă greșită
        }

        // --- C. Dacă parola e bună, CĂUTĂM detaliile userului ---
        // Extragem doar atributele definite în config (partea dreaptă a array-ului)
        $attributes_to_fetch = array_values($config['attributes_map']);
        
        // Efectuăm căutarea în AD
        $search = ldap_search($ldap_conn, $base_dn, $search_filter, $attributes_to_fetch);
        
        if (!$search) return false; // Ceva dubios la structura AD

        $entries = ldap_get_entries($ldap_conn, $search);

        if ($entries['count'] == 0) return false; // Userul există, dar nu l-am găsit la search

        // --- D. Maparea datelor (AD -> PHP Array simplu) ---
        $ad_data_raw = $entries[0];
        $clean_user_data = [];

        foreach ($config['attributes_map'] as $php_key => $ad_key) {
            // AD returnează cheile cu litere mici, deci le convertim pentru siguranță
            $ad_key_lower = strtolower($ad_key);
            
            if (isset($ad_data_raw[$ad_key_lower][0])) {
                $clean_user_data[$php_key] = $ad_data_raw[$ad_key_lower][0];
            } else {
                $clean_user_data[$php_key] = null; // Câmp gol în AD
            }
        }

        ldap_unbind($ldap_conn);
        
        // Returnăm array-ul curat (ex: ['nume' => 'Popescu', 'email' => '...'])
        return $clean_user_data; 

    } catch (Exception $e) {
        return false;
    }
}
?>