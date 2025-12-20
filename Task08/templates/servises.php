<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выполненные работы мастера</title>
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
            <!-- Форма создания/редактирования услуги -->
            <h1><?= $subAction === 'edit' ? 'Редактирование услуги' : 'Добавление выполненной работы' ?></h1>
            
            <form method="POST" class="form">
                <?php if ($subAction === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $service['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Услуга *</label>
                    <select name="service_id" required>
                        <option value="">-- Выберите услугу --</option>
                        <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>" 
                            <?= ($service['service_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= html($s['title']) ?> (<?= formatPrice($s['price']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Клиент *</label>
                    <select name="client_id" required>
                        <option value="">-- Выберите клиента --</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" 
                            <?= ($service['client_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= html($c['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Дата *</label>
                    <input type="date" name="date" value="<?= isset($service['date']) ? date('Y-m-d', strtotime($service['date'])) : date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Время *</label>
                    <input type="time" name="time" value="<?= isset($service['date']) ? date('H:i', strtotime($service['date'])) : '10:00' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Стоимость (руб.) *</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= html($service['fixed_price'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Продолжительность (минут)</label>
                    <input type="number" name="duration" min="1" value="<?= html($service['duration'] ?? '60') ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn save">Сохранить</button>
                    <a href="index.php?action=services&master_id=<?= $masterId ?>" class="btn cancel">Отмена</a>
                </div>
            </form>
            
        <?php elseif ($subAction === 'delete'): ?>
            <!-- Подтверждение удаления услуги -->
            <div class="confirmation">
                <h2>Удаление выполненной работы</h2>
                <p>Вы действительно хотите удалить услугу "<?= html($service['title']) ?>" от <?= formatDateTime($service['date']) ?>?</p>
                <p>Это действие невозможно отменить.</p>
                
                <form method="POST" class="confirmation-actions">
                    <button type="submit" name="confirm" value="1" class="btn delete">Удалить</button>
                    <a href="index.php?action=services&master_id=<?= $masterId ?>" class="btn cancel">Отмена</a>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Список выполненных работ -->
            <h1>Выполненные работы мастера: <?= html($master['full_name']) ?></h1>
            
            <table>
                <thead>
                    <tr>
                        <th>Услуга</th>
                        <th>Дата</th>
                        <th>Стоимость</th>
                        <th>Клиент</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="5" class="no-data">Нет выполненных работ</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?= html($service['service_name']) ?></td>
                            <td><?= formatDateTime($service['date']) ?></td>
                            <td><?= formatPrice($service['actual_price']) ?></td>
                            <td><?= html($service['client_name']) ?></td>
                            <td class="actions">
                                <a href="index.php?action=services&master_id=<?= $masterId ?>&sub_action=edit&id=<?= $service['id'] ?>" class="btn edit">Редактировать</a>
                                <a href="index.php?action=services&master_id=<?= $masterId ?>&sub_action=delete&id=<?= $service['id'] ?>" class="btn delete">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="footer">
                <a href="index.php?action=services&master_id=<?= $masterId ?>&sub_action=create" class="btn add">Добавить выполненную работу</a>
                <a href="index.php" class="btn back">Назад к списку мастеров</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>