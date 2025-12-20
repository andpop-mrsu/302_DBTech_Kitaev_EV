<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>График работы мастера</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>css/styles.css">
</head>
<body>
    <div class="container">
        <?php
        $flash = getFlash();
        if ($flash): ?>
            <div class="alert <?= $flash['type'] ?>"><?= html($flash['message']) ?></div>
        <?php endif; ?>
        
        <?php if ($subAction === 'create' || $subAction === 'edit'): ?>
            <!-- Форма создания/редактирования графика -->
            <h1><?= $subAction === 'edit' ? 'Редактирование графика' : 'Добавление дня в график' ?></h1>
            
            <form method="POST" class="form">
                <?php if ($subAction === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $schedule['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Дата *</label>
                    <input type="date" name="work_date" value="<?= html($schedule['work_date'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Время начала *</label>
                    <input type="time" name="start_time" value="<?= html($schedule['start_time'] ?? '09:00') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Время окончания *</label>
                    <input type="time" name="end_time" value="<?= html($schedule['end_time'] ?? '18:00') ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn save">Сохранить</button>
                    <a href="index.php?action=schedule&master_id=<?= $masterId ?>" class="btn cancel">Отмена</a>
                </div>
            </form>
            
        <?php elseif ($subAction === 'delete'): ?>
            <!-- Подтверждение удаления графика -->
            <div class="confirmation">
                <h2>Удаление записи графика</h2>
                <p>Вы действительно хотите удалить запись графика на <?= formatDate($schedule['work_date']) ?>?</p>
                <p>Это действие невозможно отменить.</p>
                
                <form method="POST" class="confirmation-actions">
                    <button type="submit" name="confirm" value="1" class="btn delete">Удалить</button>
                    <a href="index.php?action=schedule&master_id=<?= $masterId ?>" class="btn cancel">Отмена</a>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Список графика работы -->
            <h1>График работы мастера: <?= html($master['full_name']) ?></h1>
            
            <table>
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Начало работы</th>
                        <th>Окончание работы</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="4" class="no-data">График работы не установлен</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= formatDate($schedule['work_date']) ?></td>
                            <td><?= html($schedule['start_time']) ?></td>
                            <td><?= html($schedule['end_time']) ?></td>
                            <td class="actions">
                                <a href="index.php?action=schedule&master_id=<?= $masterId ?>&sub_action=edit&id=<?= $schedule['id'] ?>" class="btn edit">Редактировать</a>
                                <a href="index.php?action=schedule&master_id=<?= $masterId ?>&sub_action=delete&id=<?= $schedule['id'] ?>" class="btn delete">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="footer">
                <a href="index.php?action=schedule&master_id=<?= $masterId ?>&sub_action=create" class="btn add">Добавить день в график</a>
                <a href="index.php" class="btn back">Назад к списку мастеров</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>