<?php
// Ersetze diese Werte durch deine tatsächlichen Client ID, Client Secret und Redirect URI
$clientId = '';
$clientSecret = '';
$redirectUri = 'http://ventus-test.de/callback.php';

// Name der SQLite-Datenbankdatei
$dbFile = 'discord_data.db';

// Verbindung zur SQLite-Datenbank herstellen und Tabelle erstellen, falls nicht existiert
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Erstelle die Tabelle 'users', falls sie nicht existiert
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            discord_id TEXT NOT NULL UNIQUE,
            username TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    echo "Datenbank und Tabelle erfolgreich erstellt!<br>";
} catch (PDOException $e) {
    die('Verbindung zur Datenbank fehlgeschlagen: ' . $e->getMessage());
}

// Prüfen, ob 'code' Parameter in der URL existiert
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Tausche den Code gegen ein Access Token
    $tokenUrl = 'https://discord.com/api/oauth2/token';
    $data = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($tokenUrl, false, $context);

    if ($response === FALSE) {
        die('Fehler beim Abrufen des Tokens: Keine Antwort vom Server.');
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['access_token'])) {
        $accessToken = $responseData['access_token'];

        // Benutzerinformationen mit dem Access Token abrufen
        $userUrl = 'https://discord.com/api/users/@me';
        $userOptions = [
            'http' => [
                'header' => "Authorization: Bearer $accessToken",
            ],
        ];

        $userContext  = stream_context_create($userOptions);
        $userResponse = @file_get_contents($userUrl, false, $userContext);
        $userData = json_decode($userResponse, true);

        if ($userData) {
            // Benutzerinformationen in der SQLite-Datenbank speichern
            $discordId = $userData['id'];
            $username = $userData['username'];

            // Überprüfe, ob der Benutzer bereits existiert
            $stmt = $pdo->prepare("SELECT * FROM users WHERE discord_id = ?");
            $stmt->execute([$discordId]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingUser) {
                // Benutzer hinzufügen
                $stmt = $pdo->prepare("INSERT INTO users (discord_id, username) VALUES (?, ?)");
                $stmt->execute([$discordId, $username]);
                echo 'Hallo, ' . htmlspecialchars($username) . '! Deine Discord-ID ist ' . htmlspecialchars($discordId) . '. Die Daten wurden in der Datenbank gespeichert.';
            } else {
                echo 'Benutzer existiert bereits in der Datenbank.';
            }
        } else {
            echo 'Fehler beim Abrufen der Benutzerdaten!';
        }
    } else {
        echo 'Fehler beim Abrufen des Tokens: ' . htmlspecialchars($responseData['error_description'] ?? 'Unbekannter Fehler') . ' (' . htmlspecialchars($responseData['error'] ?? 'keine Fehlerangabe') . ')';
    }
} else {
    echo 'Code-Parameter nicht gefunden!';
}
?>
