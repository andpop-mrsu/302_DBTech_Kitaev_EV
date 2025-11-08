#!/bin/bash
chcp 65001

sqlite3 movies_rating.db < db_init.sql

echo "1. Для каждого фильма выведите его название, год выпуска и средний рейтинг. Дополнительно добавьте столбец rank_by_avg_rating, в котором укажите ранг фильма среди всех фильмов по убыванию среднего рейтинга (фильмы с одинаковым средним рейтингом должны получить одинаковый ранг). Используйте оконную функцию RANK() или DENSE_RANK(). В результирующем наборе данных оставить 10 фильмов с наибольшим рангом."
echo --------------------------------------------------
sqlite3 movies_rating.db -box -echo "SELECT m.title, m.year, ROUND(AVG(r.rating), 2) as avg_rating, DENSE_RANK() OVER (ORDER BY AVG(r.rating) DESC) as rank_by_avg_rating FROM movies m LEFT JOIN ratings r ON m.id = r.movie_id GROUP BY m.id, m.title, m.year HAVING avg_rating IS NOT NULL ORDER BY rank_by_avg_rating LIMIT 10;"
echo " "

echo "2. С помощью рекурсивного CTE выделить все жанры фильмов, имеющиеся в таблице movies. Для каждого жанра рассчитать средний рейтинг avg_rating фильмов в этом жанре. Выведите genre, avg_rating и ранг жанра по убыванию среднего рейтинга, используя оконную функцию RANK()."
echo --------------------------------------------------
sqlite3 movies_rating.db -box -echo "WITH RECURSIVE split_genres AS (SELECT id, title, TRIM(SUBSTR(genres || '|', 1, INSTR(genres || '|', '|') - 1)) AS genre, TRIM(SUBSTR(genres || '|', INSTR(genres || '|', '|') + 1)) AS rest FROM movies WHERE genres IS NOT NULL UNION ALL SELECT id, title, TRIM(SUBSTR(rest, 1, INSTR(rest, '|') - 1)) AS genre, TRIM(SUBSTR(rest, INSTR(rest, '|') + 1)) AS rest FROM split_genres WHERE rest != ''), genre_avg AS (SELECT sg.genre, AVG(r.rating) AS avg_rating FROM split_genres sg JOIN ratings r ON sg.id = r.movie_id WHERE sg.genre != '' GROUP BY sg.genre) SELECT genre, ROUND(avg_rating, 2) AS avg_rating, RANK() OVER (ORDER BY avg_rating DESC) AS rank FROM genre_avg ORDER BY avg_rating DESC;"
echo " "

echo "3. Посчитайте количество фильмов в каждом жанре. Выведите два столбца: genre и movie_count, отсортировав результат по убыванию количества фильмов."
echo --------------------------------------------------
sqlite3 movies_rating.db -box -echo "WITH RECURSIVE split(genre, rest) AS (SELECT CASE WHEN instr(genres, '|') > 0 THEN substr(genres, 1, instr(genres, '|') - 1) ELSE genres END, CASE WHEN instr(genres, '|') > 0 THEN substr(genres, instr(genres, '|') + 1) ELSE '' END FROM movies UNION ALL SELECT CASE WHEN instr(rest, '|') > 0 THEN substr(rest, 1, instr(rest, '|') - 1) ELSE rest END, CASE WHEN instr(rest, '|') > 0 THEN substr(rest, instr(rest, '|') + 1) ELSE '' END FROM split WHERE length(rest) > 0) SELECT genre, COUNT(*) as movie_count FROM split WHERE length(genre) > 0 GROUP BY genre ORDER BY movie_count DESC;"
echo " "

echo "4. Найдите жанры, в которых чаще всего оставляют теги (комментарии). Для этого подсчитайте общее количество записей в таблице tags для фильмов каждого жанра. Выведите genre, tag_count и долю этого жанра в общем числе тегов (tag_share), выраженную в процентах."
echo --------------------------------------------------
sqlite3 movies_rating.db -box -echo "WITH RECURSIVE split_genres AS (SELECT id, title, TRIM(SUBSTR(genres || '|', 1, INSTR(genres || '|', '|') - 1)) AS genre, TRIM(SUBSTR(genres || '|', INSTR(genres || '|', '|') + 1)) AS rest FROM movies WHERE genres IS NOT NULL UNION ALL SELECT id, title, TRIM(SUBSTR(rest, 1, INSTR(rest, '|') - 1)) AS genre, TRIM(SUBSTR(rest, INSTR(rest, '|') + 1)) AS rest FROM split_genres WHERE rest != ''), genre_tags AS (SELECT sg.genre, COUNT(*) AS tag_count FROM split_genres sg JOIN tags t ON sg.id = t.movie_id WHERE sg.genre != '' GROUP BY sg.genre) SELECT genre, tag_count, ROUND(100.0 * tag_count / SUM(tag_count) OVER(), 2) AS tag_share FROM genre_tags ORDER BY tag_count DESC;"
echo " "

echo "5. Для каждого пользователя рассчитайте: общее количество выставленных оценок, средний выставленный рейтинг, дату первой и последней оценки (по полю timestamp в таблице ratings). Выведите user_id, rating_count, avg_rating, first_rating_date, last_rating_date. Отсортируйте результат по убыванию количества оценок и выведите только 10 первых строк."
echo --------------------------------------------------
sqlite3 movies_rating.db -box -echo "SELECT u.id as user_id, COUNT(r.id) as rating_count, ROUND(AVG(r.rating), 2) as avg_rating, datetime(MIN(r.timestamp), 'unixepoch') as first_rating_date, datetime(MAX(r.timestamp), 'unixepoch') as last_rating_date FROM users u LEFT JOIN ratings r ON u.id = r.user_id GROUP BY u.id HAVING rating_count > 0 ORDER BY rating_count DESC LIMIT 10;"
echo " "

echo "6. Сегментируйте пользователей по типу поведения:* «Комментаторы» — пользователи, у которых количество тегов (tags) больше количества оценок (ratings), * «Оценщики» — наоборот, оценок больше, чем тегов, * «Активные» — и оценок, и тегов ≥ 10, * «Пассивные» — и оценок, и тегов < 5. Выведите user_id, общее число оценок, общее число тегов и категорию поведения. Используйте CASE."
echo --------------------------------------------------
sqlite3 movies_rating.db -box -echo "WITH user_stats AS (SELECT u.id as user_id, COUNT(DISTINCT r.id) as rating_count, COUNT(DISTINCT t.id) as tag_count FROM users u LEFT JOIN ratings r ON u.id = r.user_id LEFT JOIN tags t ON u.id = t.user_id GROUP BY u.id) SELECT user_id, rating_count, tag_count, CASE WHEN rating_count >= 10 AND tag_count >= 10 THEN 'Активные' WHEN rating_count < 5 AND tag_count < 5 THEN 'Пассивные' WHEN tag_count > rating_count THEN 'Комментаторы' WHEN rating_count > tag_count THEN 'Оценщики' ELSE 'Сбалансированные' END as behavior_category FROM user_stats ORDER BY user_id;"
echo " "

echo "7. Для каждого пользователя выведите его имя и последний фильм, который он оценил (по времени из ratings.timestamp). Если пользователь не оценивал ни одного фильма, он всё равно должен быть в результате (с NULL в полях фильма). Результат: user_id, name, last_rated_movie_title, last_rating_timestamp."
echo --------------------------------------------------
sqlite3 movies_rating.db -box -echo "WITH last_ratings AS (SELECT r.user_id, r.movie_id, r.timestamp, ROW_NUMBER() OVER (PARTITION BY r.user_id ORDER BY r.timestamp DESC) as rn FROM ratings r) SELECT u.id as user_id, u.name, m.title as last_rated_movie_title, datetime(lr.timestamp, 'unixepoch') as last_rating_timestamp FROM users u LEFT JOIN last_ratings lr ON u.id = lr.user_id AND lr.rn = 1 LEFT JOIN movies m ON lr.movie_id = m.id ORDER BY u.id;"
