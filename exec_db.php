<?php
require_once 'includes/db_connection.php';

try {
    $sql = file_get_contents('db/baseDATABASE.sql');
    if ($sql) {
        $pdo->exec($sql);
        echo "Base schemas executed successfully!<br>";
    } else {
        echo "Could not read db/baseDATABASE.sql<br>";
    }

    $update = file_get_contents('db/update_schema.sql');
    if ($update) {
        $pdo->exec($update);
        echo "Update schema executed successfully!<br>";
    }

    echo "Done!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
