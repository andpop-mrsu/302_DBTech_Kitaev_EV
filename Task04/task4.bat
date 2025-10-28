#!/bin/bash
chcp 65001

"sqlite3 movies_rating.db < db_init.sql"

echo --------------------------------------------------
echo "1. Найти все пары пользователей, оценивших один и тот же фильм. Устранить дубликаты, проверить отсутствие пар с самим собой. Для каждой пары должны быть указаны имена пользователей и название фильма, который они ценили. В списке оставить первые 100 записей."
sqlite3 movies_rating.db -box -echo "SELECT DISTINCT u1.name AS 'Пользователь 1', u2.name AS 'Пользователь 2', m.title AS 'Название Фильма' FROM ratings r1 JOIN ratings r2 ON r1.movie_id = r2.movie_id AND r1.user_id < r2.user_id JOIN users u1 ON r1.user_id = u1.id JOIN users u2 ON r2.user_id = u2.id JOIN movies m ON r1.movie_id = m.id LIMIT 100"


echo --------------------------------------------------
echo "2. Найти 10 самых свежих оценок от разных пользователей, вывести названия фильмов, имена пользователей, оценку, дату отзыва в формате ГГГГ-ММ-ДД."
sqlite3 movies_rating.db -box -echo "WITH LatestRatings AS (SELECT r.*, ROW_NUMBER() OVER (PARTITION BY r.user_id ORDER BY r.timestamp DESC) as rn FROM ratings r) SELECT m.title AS 'Название фильма', u.name AS 'Пользователи', lr.rating AS 'Рейтинг', date(datetime(lr.timestamp, 'unixepoch')) AS 'Дата просмотра' FROM LatestRatings lr JOIN movies m ON lr.movie_id = m.id JOIN users u ON lr.user_id = u.id WHERE lr.rn = 1 ORDER BY lr.timestamp DESC LIMIT 10;"


echo --------------------------------------------------
echo "3. Вывести в одном списке все фильмы с максимальным средним рейтингом и все фильмы с минимальным средним рейтингом. Общий список отсортировать по году выпуска и названию фильма. В зависимости от рейтинга в колонке 'Рекомендуем' для фильмов должно быть написано 'Да' или 'Нет'."
sqlite3 movies_rating.db -box -echo "WITH MovieRatings AS (SELECT m.id, m.title, m.year, AVG(r.rating) AS avg_rating FROM movies m JOIN ratings r ON m.id = r.movie_id GROUP BY m.id, m.title, m.year), MaxMinRatings AS (SELECT MAX(avg_rating) AS max_rating, MIN(avg_rating) AS min_rating FROM MovieRatings WHERE avg_rating IS NOT NULL) SELECT mr.title AS 'Название фильма', mr.year AS 'Год', ROUND(mr.avg_rating, 2) AS 'Средний рейтинг', CASE WHEN mr.avg_rating = (SELECT max_rating FROM MaxMinRatings) THEN 'Да' WHEN mr.avg_rating = (SELECT min_rating FROM MaxMinRatings) THEN 'Нет' END AS 'Рекомендуем' FROM MovieRatings mr WHERE mr.avg_rating = (SELECT max_rating FROM MaxMinRatings) OR mr.avg_rating = (SELECT min_rating FROM MaxMinRatings) ORDER BY mr.year, mr.title;"


echo --------------------------------------------------
echo "4. Вычислить количество оценок и среднюю оценку, которую дали фильмам пользователи-женщины в период с 2010 по 2012 год."
sqlite3 movies_rating.db -box -echo "SELECT COUNT(*) AS 'Количество оценок', AVG(r.rating) AS 'Средняя оценка' FROM ratings r JOIN users u ON r.user_id = u.id WHERE u.gender = 'female' AND strftime('%Y', datetime(r.timestamp, 'unixepoch')) BETWEEN '2010' AND '2012'"


echo --------------------------------------------------
echo "5. Составить список фильмов с указанием их средней оценки и места в рейтинге по средней оценке. Полученный список отсортировать по году выпуска и названиям фильмов. В списке оставить первые 20 записей."
sqlite3 movies_rating.db -box -echo "SELECT title AS 'Название фильма', year AS 'Год выпуска', AVG(r.rating) AS 'Средняя оценка', RANK() OVER (ORDER BY AVG(r.rating) DESC) AS 'Место в рейтинге' FROM movies m JOIN ratings r ON m.id = r.movie_id GROUP BY m.id ORDER BY year ASC, title ASC LIMIT 20"


echo --------------------------------------------------
echo "6. Вывести список из 10 последних зарегистрированных пользователей в формате 'Фамилия Имя|Дата регистрации' (сначала фамилия, потом имя)."
sqlite3 movies_rating.db -box -echo "WITH SplitNames AS (SELECT id, name, CASE WHEN INSTR(name, ' ') > 0 THEN SUBSTR(name, INSTR(name, ' ') + 1) ELSE name END AS last_name, CASE WHEN INSTR(name, ' ') > 0 THEN SUBSTR(name, 1, INSTR(name, ' ') - 1) ELSE '' END AS first_name, register_date FROM users) SELECT last_name || ' ' || first_name || '|' || register_date AS 'Фамилия Имя|Дата регистрации' FROM SplitNames ORDER BY register_date DESC LIMIT 10;"


echo --------------------------------------------------
echo "7. С помощью рекурсивного CTE составить таблицу умножения для чисел от 1 до 10."
sqlite3 movies_rating.db -box -echo "WITH RECURSIVE numbers(n) AS ( SELECT 1 UNION ALL SELECT n + 1 FROM numbers WHERE n < 10) SELECT a.n || 'x' || b.n || '=' || (a.n * b.n) AS multiplication FROM numbers a, numbers b ORDER BY a.n, b.n;"

echo --------------------------------------------------
echo "8. С помощью рекурсивного CTE выделить все жанры фильмов, имеющиеся в таблице movies (каждый жанр в отдельной строке)."
sqlite3 movies_rating.db -box -echo "WITH RECURSIVE split_genres(genre, remaining, movie_id) AS (SELECT CASE WHEN INSTR(genres, '|') > 0 THEN SUBSTR(genres, 1, INSTR(genres, '|') - 1) ELSE genres END AS genre, CASE WHEN INSTR(genres, '|') > 0 THEN SUBSTR(genres, INSTR(genres, '|') + 1) ELSE '' END AS remaining, id AS movie_id FROM movies WHERE genres != '(no genres listed)' UNION ALL SELECT CASE WHEN INSTR(remaining, '|') > 0 THEN SUBSTR(remaining, 1, INSTR(remaining, '|') - 1) ELSE remaining END AS genre, CASE WHEN INSTR(remaining, '|') > 0 THEN SUBSTR(remaining, INSTR(remaining, '|') + 1) ELSE '' END AS remaining, movie_id FROM split_genres WHERE remaining != '') SELECT DISTINCT genre AS 'Уникальные жанры' FROM split_genres WHERE genre != '' AND genre != '(no genres listed)' ORDER BY genre;"
