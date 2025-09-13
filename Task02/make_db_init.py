import csv
from datetime import datetime  

def make_db_init():
    sql_script = """-- SQL Script for movies_rating.db initialization
-- Generated on: {date}
-- Database: movies_rating.db

BEGIN TRANSACTIONS
-- Drop tables if the exist
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS movies;
DROP TABLE IF EXISTS users;

-- Create movies table
CREATE TABLE movies (
    movieId INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    genres TEXT
);

-- Create users table
CREATE TABLE users (
    userId INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    genres TEXT
);
