<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';
$masterId = $_GET['master_id'] ?? 0;

// Обработка основных действий
if ($action === 'create' || $action === 'edit') {
    // Форма создания/редактирования мастера
    $master = null;
    if ($action === 'edit' && isset($_GET['id'])) {
        $master = $db->getMaster($_GET['id']);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
        if ($action === 'edit' && isset($_GET['id'])) {
            $data['id'] = $_GET['id'];
        }
        
        if (isset($data['commission_percent'])) {
            $data['commission_percent'] = $data['commission_percent'] / 100;
        }
        
        if ($db->saveMaster($data)) {
            setFlash('Данные мастера сохранены');
            redirect('index.php');
        }
    }
    
    require '../templates/masters.php';
    
} elseif ($action === 'delete') {
    // Удаление мастера
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
        $db->deleteMaster($_GET['id']);
        setFlash('Мастер удален');
        redirect('index.php');
    }
    
    $master = $db->getMaster($_GET['id']);
    require '../templates/masters.php';
    
} elseif ($action === 'schedule' || $action === 'services') {
    // Работа с графиком или услугами
    $subAction = $_GET['sub_action'] ?? 'list';
    $id = $_GET['id'] ?? 0;
    
    if ($action === 'schedule') {
        // График работы
        if ($subAction === 'create' || $subAction === 'edit') {
            $schedule = null;
            if ($subAction === 'edit') {
                $schedule = $db->getScheduleItem($id);
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = $_POST;
                $data['master_id'] = $masterId;
                if ($subAction === 'edit') {
                    $data['id'] = $id;
                }
                
                if ($db->saveSchedule($data)) {
                    setFlash('График сохранен');
                    redirect("index.php?action=schedule&master_id=$masterId");
                }
            }
            
            require '../templates/schedule.php';
            
        } elseif ($subAction === 'delete') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
                $db->deleteSchedule($id);
                setFlash('Запись графика удалена');
                redirect("index.php?action=schedule&master_id=$masterId");
            }
            
            $schedule = $db->getScheduleItem($id);
            require '../templates/schedule.php';
            
        } else {
            // Список графика
            $master = $db->getMaster($masterId);
            $schedules = $db->getSchedule($masterId);
            require '../templates/schedule.php';
        }
        
    } else {
        // Выполненные работы
        if ($subAction === 'create' || $subAction === 'edit') {
            $service = null;
            $services = $db->getAllServices();
            $clients = $db->getAllClients();
            
            if ($subAction === 'edit') {
                $service = $db->getService($id);
                if (!$service || $service['master_id'] != $masterId) {
                    setFlash('Запись не найдена', 'error');
                    redirect("index.php?action=services&master_id=$masterId");
                }
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = $_POST;
                $data['master_id'] = $masterId;
                if ($subAction === 'edit') {
                    $data['id'] = $id;
                }
                
                if ($db->saveService($data)) {
                    setFlash('Услуга сохранена');
                    redirect("index.php?action=services&master_id=$masterId");
                }
            }
            
            require '../templates/services.php';
            
        } elseif ($subAction === 'delete') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
                $db->deleteService($id);
                setFlash('Услуга удалена');
                redirect("index.php?action=services&master_id=$masterId");
            }
            
            $service = $db->getService($id);
            if (!$service || $service['master_id'] != $masterId) {
                setFlash('Запись не найдена', 'error');
                redirect("index.php?action=services&master_id=$masterId");
            }
            
            require '../templates/services.php';
            
        } else {
            // Список выполненных работ
            $master = $db->getMaster($masterId);
            $services = $db->getCompletedServices($masterId);
            require '../templates/services.php';
        }
    }
    
} else {
    // Главная страница - список мастеров
    $masters = $db->getMasters();
    require '../templates/masters.php';
}
?>