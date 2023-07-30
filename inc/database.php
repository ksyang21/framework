<?php

use Database\Database;

const HOST    = "127.0.0.1";
const USER    = "kahsin";
const PASS    = "kpb514PHB";
const DB_NAME = "kahsin_portfolio";
const PORT    = 3306;

$mysqli = new mysqli(HOST, USER, PASS, DB_NAME, PORT);
$sql_conn = new Database($mysqli);