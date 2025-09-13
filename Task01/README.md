# Структура файлов данных

## movies.csv

**Столбцы**
- `movieId` (integer) - уникальный идентификатор фильма
- `title` (string) - название фильма с годом выпуска в скобках
- `genres` (string) - список жанров через вертикальную черту `|`

## ratings.csv

**Столбцы**
- `userId` (integer) - уникальный идентификатор пользователя
- `movieId` (integer) - идентификатор фильма
- `rating` (float) - оценка от 0.5 до 5.0 с шагом 0.5
- `timestamp` (integer) - время в формате UNIX

## tags.csv 

**Столбцы**
- `userId` (integer) - уникальный идентификатор пользователя
- `movieId` (integer) - идентификатор фильма
- `tag` (string) - пользовательский тег
- `timestamp` (integer) - время в формате UNIX

## users.txt

**Столбцы**
- `userId` (integer) - уникальный идентификатор пользователя
- `name` (string) - полное имя пользователя
- `email` (string) - адрес электронной почты
- `gender` (string) - пол
- `date_of_birth` (date) - дата рождение в формате YYYY-MM-DD
- `profession` (string) - профессия пользователя