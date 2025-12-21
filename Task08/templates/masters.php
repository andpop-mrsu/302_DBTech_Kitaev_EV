<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Парикмахерская - Мастера</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Список мастеров</h1>

        <?php
        $flash = getFlash();
        if ($flash): ?>
            <div class="alert <?= htmlspecialchars($flash["type"]) ?>"><?= html(
    $flash["message"],
) ?></div>
        <?php endif;
        ?>

        <?php if ($action === "create" || $action === "edit"): ?>
            <h2><?= $action === "edit"
                ? "Редактировать мастера"
                : "Добавить мастера" ?></h2>
            <form action="index.php?action=<?= $action .
                (isset($master["id"])
                    ? "&id=" . $master["id"]
                    : "") ?>" method="post">
                <div class="form-group">
                    <label for="full_name">Имя Фамилия:</label>
                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars(
                        $master["full_name"] ?? "",
                    ) ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Телефон:</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars(
                        $master["phone"] ?? "",
                    ) ?>">
                </div>
                <div class="form-group">
                    <label for="specialization">Специализация:</label>
                    <select id="specialization" name="specialization" required>
                        <option value="MALE" <?= ($master["specialization"] ??
                            "") ===
                        "MALE"
                            ? "selected"
                            : "" ?>>Мужской</option>
                        <option value="FEMALE" <?= ($master["specialization"] ??
                            "") ===
                        "FEMALE"
                            ? "selected"
                            : "" ?>>Женский</option>
                        <option value="UNIVERSAL" <?= ($master[
                            "specialization"
                        ] ??
                            "") ===
                        "UNIVERSAL"
                            ? "selected"
                            : "" ?>>Универсал</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="commission_percent">Комиссия (%):</label>
                    <input type="number" id="commission_percent" name="commission_percent" value="<?= htmlspecialchars(
                        ($master["commission_percent"] ?? 0) * 100,
                    ) ?>" min="0" max="100" step="1" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="button">Сохранить</button>
                    <a href="index.php" class="button">Отмена</a>
                </div>
            </form>
        <?php elseif ($action === "delete" && isset($master)): ?>
            <h2>Удалить мастера</h2>
            <p>Вы уверены, что хотите удалить мастера "<?= htmlspecialchars(
                $master["full_name"],
            ) ?>"?</p>
            <form action="index.php?action=delete&id=<?= $master[
                "id"
            ] ?>" method="post">
                <div class="form-group">
                    <button type="submit" name="confirm" value="1" class="button">Удалить</button>
                    <a href="index.php" class="button">Отмена</a>
                </div>
            </form>
        <?php endif; ?>

        <?php if (!empty($masters)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Специализация</th>
                        <th>Комиссия (%)</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($masters as $master): ?>
                        <tr>
                            <td><?= htmlspecialchars(
                                $master["full_name"],
                            ) ?></td>
                            <td><?= htmlspecialchars($master["phone"]) ?></td>
                            <td><?= htmlspecialchars(
                                $master["specialization"],
                            ) ?></td>
                            <td><?= htmlspecialchars(
                                $master["commission_percent"] * 100,
                            ) ?></td>
                            <td class="actions">
                                <a href="index.php?action=edit&id=<?= $master[
                                    "id"
                                ] ?>">Редактировать</a>
                                <a href="index.php?action=delete&id=<?= $master[
                                    "id"
                                ] ?>">Удалить</a>
                                <a href="index.php?action=schedule&master_id=<?= $master[
                                    "id"
                                ] ?>">График</a>
                                <a href="index.php?action=services&master_id=<?= $master[
                                    "id"
                                ] ?>">Выполненные работы</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Нет мастеров для отображения.</p>
        <?php endif; ?>

        <div class="add-button-container">
            <a href="index.php?action=create" class="button">Добавить мастера</a>
        </div>
    </div>
</body>
</html>
