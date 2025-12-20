<?php
require_once __DIR__ . '/database.php';

class Master {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query(
            "SELECT m.*,
             CASE m.specialization
                WHEN 'MALE' THEN 'Мужской мастер'
                WHEN 'FEMALE' THEN 'Женский мастер'
                WHEN 'UNIVERSAL' THEN 'Универсальный мастер'
             END as specialization_text
             FROM masters m 
             ORDER BY 
             SUBSTR(m.full_name, 1, INSTR(m.full_name || ' ', ' ') - 1)"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare('SELECT * FROM masters WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare(
            'INSERT INTO masters (full_name, phone, specialization, commission_percent, is_active) 
             VALUES (?, ?, ?, ?, ?)'
        );
        return $stmt->execute([
            $data['full_name'],
            $data['phone'] ?? '',
            $data['specialization'],
            $data['commission_percent'],
            $data['is_active'] ?? 1
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare(
            'UPDATE masters SET 
             full_name = ?, phone = ?, specialization = ?, commission_percent = ?, is_active = ?
             WHERE id = ?'
        );
        return $stmt->execute([
            $data['full_name'],
            $data['phone'] ?? '',
            $data['specialization'],
            $data['commission_percent'],
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare('DELETE FROM masters WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getSchedule($masterId) {
        $stmt = $this->db->prepare(
            "SELECT s.*,
             strftime('%w', s.work_date) as day_of_week
             FROM schedules s
             WHERE s.master_id = ? 
             ORDER BY s.work_date DESC"
        );
        $stmt->execute([$masterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompletedServices($masterId) {
        $stmt = $this->db->prepare(
            "SELECT 
                asv.id,
                s.title as service_name,
                a.start_datetime as date,
                asv.fixed_price as actual_price,
                c.full_name as client_name
             FROM appointments a
             JOIN appointment_services asv ON a.id = asv.appointment_id
             JOIN services s ON asv.service_id = s.id
             JOIN clients c ON a.client_id = c.id
             WHERE a.master_id = ? AND a.status = 'COMPLETED'
             ORDER BY a.start_datetime DESC"
        );
        $stmt->execute([$masterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>