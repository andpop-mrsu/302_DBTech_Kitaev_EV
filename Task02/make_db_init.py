import csv
import os
import re
import sqlite3

def extract_year(t):
    if not t:
        return None
    m = re.search(r'\((\d{4})\)$', t)
    return int(m.group(1)) if m else None

def clean_title(t):
    return re.sub(r'\s*\(\d{4}\)$', '', t).strip() if t else ""

def esc(s): return "" if s is None else str(s).replace("'", "''")
def to_int(v, d=None):
    try: return int(v)
    except: return d
def to_float(v, d=None):
    try: return float(v)
    except: return d
def detect_delimiter(p):
    try:
        with open(p, encoding='utf-8') as f:
            l = f.readline()
            if '|' in l: return '|'
            if '\t' in l: return '\t'
            return ',' if ',' in l else ','
    except: return ','

def insert_stmt(table, cols, vals):
    parts=[]
    for v in vals:
        if v is None: parts.append('NULL')
        elif isinstance(v,(int,float)): parts.append(str(v))
        else: parts.append(f"'{esc(v)}'")
    return f"INSERT INTO {table} ({', '.join(cols)}) VALUES ({', '.join(parts)});"

def generate_sql():
    o = []
    o += [
        "DROP TABLE IF EXISTS tags;",
        "DROP TABLE IF EXISTS ratings;",
        "DROP TABLE IF EXISTS users;",
        "DROP TABLE IF EXISTS movies;",
        "",
        "CREATE TABLE movies (id INTEGER PRIMARY KEY, title TEXT NOT NULL, year INTEGER, genres TEXT);",
        "CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT, gender TEXT, register_date TEXT, occupation TEXT);",
        "CREATE TABLE ratings (id INTEGER PRIMARY KEY, user_id INTEGER, movie_id INTEGER, rating REAL, timestamp INTEGER);",
        "CREATE TABLE tags (id INTEGER PRIMARY KEY, user_id INTEGER, movie_id INTEGER, tag TEXT, timestamp INTEGER);",
        ""
    ]

    try:
        with open('movies.csv', encoding='utf-8') as f:
            first = f.readline(); f.seek(0)
            reader = csv.DictReader(f)
            idf = 'movieId' if ('movieId' in (reader.fieldnames or []) or 'movieId' in first) else ('movieID' if ('movieID' in (reader.fieldnames or []) or 'movieID' in first) else 'movieId')
            for r in reader:
                mid = to_int(r.get(idf, r.get('movieId','')))
                title = r.get('title','')
                if not (mid and title): continue
                o.append(insert_stmt('movies', ['id','title','year','genres'], [mid, clean_title(title), extract_year(title), r.get('genres','')]))
    except Exception as e:
        print("Ошибка movies.csv:", e)
    o.append("")

    try:
        delim = detect_delimiter('users.txt')
        with open('users.txt', encoding='utf-8') as f:
            if delim == '|':
                for line in f:
                    p = line.strip().split('|')
                    if len(p) < 2: continue
                    uid = to_int(p[0]); name = p[1]
                    if not (uid and name): continue
                    o.append(insert_stmt('users', ['id','name','email','gender','register_date','occupation'], [uid,name, p[2] if len(p)>2 else '', p[3] if len(p)>3 else '', p[4] if len(p)>4 else '', p[5] if len(p)>5 else '']))
            else:
                reader = csv.DictReader(f, delimiter=delim)
                for r in reader:
                    uid = to_int(r.get('userID') or r.get('userId') or r.get('id'))
                    name = r.get('name','')
                    if not (uid and name): continue
                    o.append(insert_stmt('users', ['id','name','email','gender','register_date','occupation'], [uid, name, r.get('email',''), r.get('gender',''), r.get('register_date') or r.get('data',''), r.get('occupation') or r.get('proffession','')]))
    except Exception as e:
        print("Ошибка users.txt:", e)
    o.append("")

    def load(path, table):
        try:
            with open(path, encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for r in reader:
                    user = to_int(r.get('userId') or r.get('userID') or r.get('userid') or r.get('user'))
                    movie = to_int(r.get('movieId') or r.get('movieID') or r.get('movie'))
                    if table == 'ratings':
                        rating = to_float(r.get('rating'))
                        ts = to_int(r.get('timestamp') or r.get('time') or 0, 0)
                        if user and movie and rating is not None:
                            o.append(insert_stmt(table, ['user_id','movie_id','rating','timestamp'], [user,movie,rating,ts]))
                    else:
                        tag = r.get('tag','')
                        ts = to_int(r.get('timestamp') or r.get('time') or 0, 0)
                        if user and movie and tag:
                            o.append(insert_stmt(table, ['user_id','movie_id','tag','timestamp'], [user,movie,tag,ts]))
        except Exception as e:
            print(f"Ошибка {path}:", e)

    load('ratings.csv','ratings'); o.append(""); load('tags.csv','tags')
    return '\n'.join(o)

def write_sql(path='db_init.sql'):
    s = generate_sql()
    with open(path, 'w', encoding='utf-8') as f:
        f.write(s)

def exec_sql(path='db_init.sql', db='movies_rating.db'):
    if os.path.exists(db):
        os.remove(db)

    with open(path, encoding='utf-8') as f:
        sql = f.read()

    conn = sqlite3.connect(db)
    try:
        conn.executescript(sql)
    finally:
        conn.close()

    print(f"База данных {db} создана")

def main():
    for fname in ('movies.csv','users.txt','ratings.csv','tags.csv'):
        if not os.path.exists(fname): print("Предупреждение: файл не найден", fname)
    write_sql('db_init.sql')
    exec_sql('db_init.sql','movies_rating.db')

if __name__ == '__main__':
    main()
