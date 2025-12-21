<?php
// From config.php
define("DB_PATH", __DIR__ . "/../data/barbershop.db");
define("SITE_URL", "/Task08/public/");

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            $dbExists = file_exists(DB_PATH);

            $this->pdo = new PDO("sqlite:" . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("PRAGMA foreign_keys = ON");

            if (!$dbExists) {
                $this->initDatabaseFromSQL();
            }
        } catch (PDOException $e) {
            die(
                "Ошибка подключения или инициализации базы данных: " .
                    $e->getMessage()
            );
        }
    }

    private function initDatabaseFromSQL()
    {
        try {
            $sqlFile = __DIR__ . "/../db_init.sql";
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $this->pdo->exec($sql);
            }
        } catch (Exception $e) {
            error_log("Ошибка создания БД из SQL файла: " . $e->getMessage());
            // Если инициализация из файла не удалась, можно предусмотреть запасной вариант
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    // Methods for Masters
    public function getMasters()
    {
        $stmt = $this->pdo->query("SELECT * FROM masters ORDER BY full_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMaster($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM masters WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveMaster($data)
    {
        if (isset($data["id"])) {
            $stmt = $this->pdo->prepare(
                "UPDATE masters SET full_name = ?, specialization = ?, is_active = ?, commission_percent = ? WHERE id = ?",
            );
            return $stmt->execute([
                $data["full_name"],
                $data["specialization"],
                $data["is_active"] ?? 0,
                $data["commission_percent"],
                $data["id"],
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO masters (full_name, phone, specialization, commission_percent, is_active) VALUES (?, ?, ?, ?, ?)",
            );
            return $stmt->execute([
                $data["full_name"],
                $data["phone"] ?? null,
                $data["specialization"],
                $data["commission_percent"],
                $data["is_active"] ?? 0,
            ]);
        }
    }

    public function deleteMaster($id)
    {
        try {
            $this->pdo->beginTransaction();

            // Manually delete appointments because of ON DELETE RESTRICT
            // appointment_services will be deleted by CASCADE from appointments
            $stmt = $this->pdo->prepare(
                "DELETE FROM appointments WHERE master_id = ?",
            );
            $stmt->execute([$id]);

            // Now it should be safe to delete the master
            // Schedules will be deleted automatically due to ON DELETE CASCADE
            $stmt = $this->pdo->prepare("DELETE FROM masters WHERE id = ?");
            $stmt->execute([$id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("deleteMaster failed: " . $e->getMessage());
            return false;
        }
    }

    // Methods for Schedule
    public function getSchedule($masterId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM schedules WHERE master_id = ? ORDER BY work_date",
        );
        $stmt->execute([$masterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getScheduleItem($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM schedules WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveSchedule($data)
    {
        if (isset($data["id"])) {
            $stmt = $this->pdo->prepare(
                "UPDATE schedules SET master_id = ?, work_date = ?, start_time = ?, end_time = ? WHERE id = ?",
            );
            return $stmt->execute([
                $data["master_id"],
                $data["work_date"],
                $data["start_time"],
                $data["end_time"],
                $data["id"],
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO schedules (master_id, work_date, start_time, end_time) VALUES (?, ?, ?, ?)",
            );
            return $stmt->execute([
                $data["master_id"],
                $data["work_date"],
                $data["start_time"],
                $data["end_time"],
            ]);
        }
    }

    public function deleteSchedule($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM schedules WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Methods for Services/Appointments
    public function getCompletedServices($masterId)
    {
        $sql = "
            SELECT
                a.id,
                a.start_datetime,
                (strftime('%s', a.end_datetime) - strftime('%s', a.start_datetime)) / 60 as duration_min,
                c.full_name as client_name,
                GROUP_CONCAT(s.title, ', ') as services_list,
                SUM(aps.fixed_price) as total_price
            FROM appointments a
            JOIN clients c ON a.client_id = c.id
            JOIN appointment_services aps ON a.id = aps.appointment_id
            JOIN services s ON aps.service_id = s.id
            WHERE a.master_id = ? AND a.status = 'COMPLETED'
            GROUP BY a.id
            ORDER BY a.start_datetime DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$masterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getService($id)
    {
        $sql = "
            SELECT
                a.*,
                c.full_name as client_name,
                GROUP_CONCAT(s.title, ', ') as services_list
            FROM appointments a
            LEFT JOIN clients c ON a.client_id = c.id
            LEFT JOIN appointment_services aps ON a.id = aps.appointment_id
            LEFT JOIN services s ON aps.service_id = s.id
            WHERE a.id = ?
            GROUP BY a.id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAppointmentService($appointmentId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointment_services WHERE appointment_id = ? LIMIT 1",
        );
        $stmt->execute([$appointmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveService($data)
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Get Service gender to determine client gender if new
            $stmt = $this->pdo->prepare(
                "SELECT target_gender FROM services WHERE id = ?",
            );
            $stmt->execute([$data["service_id"]]);
            $serviceGender = $stmt->fetchColumn();

            // 2. Get or Create Client
            $clientId = $this->getOrCreateClient(
                $data["client_first_name"],
                $data["client_last_name"],
                $serviceGender,
            );

            // 2. Prepare appointment data
            $start_datetime_str = $data["date"] . " " . $data["time"] . ":00";
            $status = "COMPLETED"; // Assuming it's always a completed work from this form

            // Calculate end_datetime
            $duration_minutes = (int) ($data["duration"] ?? 60);
            $start_datetime = new DateTime($start_datetime_str);
            $end_datetime = clone $start_datetime;
            $end_datetime->add(new DateInterval("PT{$duration_minutes}M"));
            $end_datetime_str = $end_datetime->format("Y-m-d H:i:s");

            if (isset($data["id"])) {
                // UPDATE
                $stmt = $this->pdo->prepare(
                    "UPDATE appointments SET master_id = ?, client_id = ?, start_datetime = ?, end_datetime = ?, status = ? WHERE id = ?",
                );
                $stmt->execute([
                    $data["master_id"],
                    $clientId,
                    $start_datetime_str,
                    $end_datetime_str,
                    $status,
                    $data["id"],
                ]);
                $appointmentId = $data["id"];

                // For simplicity, we'll delete and re-insert appointment_services.
                // A more robust implementation would compare and update.
                $stmt = $this->pdo->prepare(
                    "DELETE FROM appointment_services WHERE appointment_id = ?",
                );
                $stmt->execute([$appointmentId]);
            } else {
                // INSERT
                $stmt = $this->pdo->prepare(
                    "INSERT INTO appointments (master_id, client_id, start_datetime, end_datetime, status) VALUES (?, ?, ?, ?, ?)",
                );
                $stmt->execute([
                    $data["master_id"],
                    $clientId,
                    $start_datetime_str,
                    $end_datetime_str,
                    $status,
                ]);
                $appointmentId = $this->pdo->lastInsertId();
            }

            // 3. Insert into appointment_services
            $stmt = $this->pdo->prepare(
                "INSERT INTO appointment_services (appointment_id, service_id, fixed_price) VALUES (?, ?, ?)",
            );
            $stmt->execute([
                $appointmentId,
                $data["service_id"],
                $data["price"],
            ]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("saveService failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteService($id)
    {
        try {
            $this->pdo->beginTransaction();

            // First, delete from appointment_services
            $stmt = $this->pdo->prepare(
                "DELETE FROM appointment_services WHERE appointment_id = ?",
            );
            $stmt->execute([$id]);

            // Then, delete from appointments
            $stmt = $this->pdo->prepare(
                "DELETE FROM appointments WHERE id = ?",
            );
            $stmt->execute([$id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("deleteService failed: " . $e->getMessage());
            return false;
        }
    }

    // Methods for ancillary data
    public function getAllServices()
    {
        $stmt = $this->pdo->query("SELECT * FROM services ORDER BY title");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClient($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrCreateClient($firstName, $lastName, $gender = "FEMALE")
    {
        $fullName = trim($firstName . " " . $lastName);
        $stmt = $this->pdo->prepare(
            "SELECT id FROM clients WHERE full_name = ?",
        );
        $stmt->execute([$fullName]);
        $clientId = $stmt->fetchColumn();

        if ($clientId) {
            return $clientId;
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO clients (full_name, gender) VALUES (?, ?)",
            );
            $stmt->execute([$fullName, $gender]);
            return $this->pdo->lastInsertId();
        }
    }

    public function getAllClients()
    {
        $stmt = $this->pdo->query("SELECT * FROM clients ORDER BY full_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// From helpers.php
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

function setFlash($message, $type = "success")
{
    $_SESSION["flash"] = ["message" => $message, "type" => $type];
}

function getFlash()
{
    if (isset($_SESSION["flash"])) {
        $flash = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        return $flash;
    }
    return null;
}

function html($text)
{
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

function formatDate($date)
{
    return date("d.m.Y", strtotime($date));
}

function formatDateTime($datetime)
{
    return date("d.m.Y H:i", strtotime($datetime));
}

function formatPrice($price)
{
    return number_format($price, 2) . " ₽";
}
?>
