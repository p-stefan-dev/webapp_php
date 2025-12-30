<?php
// config/ldap_config.php

return [
    // --- 1. Date Conectare Server ---
    'server' => [
        'host'    => '192.168.1.10',   // IP Server AD
        'domain'  => 'firma.local',    // Domeniul (ex: spital.local)
        'port'    => 389,              // 389 sau 636 (SSL)
        // Base DN este folderul rădăcină unde căutăm userii. 
        // De obicei este: DC=nume,DC=domeniu (ex: DC=firma,DC=local)
        'base_dn' => 'DC=firma,DC=local', 
    ],

    // --- 2. Mapare Câmpuri (Stânga: Nume în PHP | Dreapta: Nume în AD) ---
    // Poți adăuga/șterge linii aici fără să modifici codul sursă.
    // Numele din dreapta (AD) trebuie să fie exact cum sunt în Active Directory (case-insensitive de obicei).
    'attributes_map' => [
        'username'    => 'samaccountname',  // Userul de login (ex: ion.popescu)
        'email'       => 'mail',            // Adresa de email
        'nume'        => 'sn',              // Numele de familie (Surname)
        'prenume'     => 'givenname',       // Prenumele
        'nume_complet'=> 'displayname',     // Nume afișat (Ion Popescu)
        'departament' => 'department',      // Departamentul
        'functie'     => 'title',           // Funcția (ex: Manager)
        'telefon'     => 'telephonenumber', // Telefon intern
        'locatie'     => 'physicaldeliveryofficename' // Birou
    ]
];
?>