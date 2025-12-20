<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            // Создаем директорию для БД, если её нет
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            
            // Проверяем, существует ли уже база данных
            $this->checkAndInitDatabase();
            
        } catch (PDOException $e) {
            die('Ошибка подключения к базе данных: ' . $e->getMessage());
        }
    }

    private function checkAndInitDatabase() {
        // Проверяем, есть ли уже таблицы
        try {
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='masters'");
            $tableExists = $stmt->fetch();
            
            if (!$tableExists) {
                // Таблиц нет, создаем БД из SQL файла
                $this->initDatabaseFromSQL();
            }
        } catch (Exception $e) {
            // Ошибка при проверке, пробуем создать БД
            $this->initDatabaseFromSQL();
        }
    }

    private function initDatabaseFromSQL() {
        $sqlFile = __DIR__ . '/../data/db_init.sql';
        
        if (file_exists($sqlFile)) {
            try {
                // Читаем SQL файл
                $sql = file_get_contents($sqlFile);
                
                // Разбиваем на отдельные запросы (убираем BEGIN TRANSACTION и COMMIT)
                $sql = str_replace(['BEGIN TRANSACTION;', 'COMMIT;'], '', $sql);
                
                // Выполняем SQL
                $this->pdo->exec($sql);
                
                error_log("База данных успешно создана из SQL файла");
            } catch (Exception $e) {
                error_log("Ошибка создания БД из SQL файла: " . $e->getMessage());
                // Создаем минимальную структуру
                $this->createMinimalSchema();
            }
        } else {
            // SQL файла нет, создаем минимальную структуру
            $this->createMinimalSchema();
        }
    }

    private function createMinimalSchema() {
        try {
            // Создаем только основные таблицы без тестовых данных
            $sql = "
            CREATE TABLE IF NOT EXISTS masters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                phone TEXT,
                specialization TEXT NOT NULL CHECK (specialization IN ('MALE', 'FEMALE', 'UNIVERSAL')),
                commission_percent REAL NOT NULL CHECK (commission_percent >= 0 AND commission_percent <= 1.0),
                is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1))
            );
            
            CREATE TABLE IF NOT EXISTS schedules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                master_id INTEGER NOT NULL,
                work_date TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                FOREIGN KEY (master_id) REFERENCES masters(id) ON DELETE CASCADE,
                UNIQUE(master_id, work_date)
            );
            
            CREATE TABLE IF NOT EXISTS services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                target_gender TEXT NOT NULL CHECK (target_gender IN ('MALE', 'FEMALE')),
                duration_min INTEGER NOT NULL CHECK (duration_min > 0),
                price REAL NOT NULL CHECK (price >= 0)
            );
            
            CREATE TABLE IF NOT EXISTS clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                phone TEXT,
                gender TEXT NOT NULL CHECK (gender IN ('MALE', 'FEMALE')),
                email TEXT
            );
            ";
            
            $this->pdo->exec($sql);
            error_log("Минимальная структура базы данных создана");
        } catch (Exception $e) {
            error_log("Ошибка создания минимальной структуры БД: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}
?>