<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::getInstance()->getConnection();

    $sql = file_get_contents(__DIR__ . '/db_init.sql');
    
    $db->exec($sql);
    
    echo "База данных успешно инициализирована!";
} catch (PDOException $e) {
    echo "Ошибка инициализации базы данных: " . $e->getMessage();
}
?>