<?php

const TOKEN = '6382807252:AAG-hWglgwqwToExgAjcrM4fPfUC5WLlprg';
const STRIPE_TOKEN = '284685063:TEST:YjgyMzE0NjMxNzRh';
const PAYMENT_IMG = 'https://domain1864238.ru/bots/3/web-apps/img/payment.jpg';

const MINIMAL_BILL = 10000;

const BASE_URL = 'https://api.telegram.org/bot' . TOKEN . '/';
const WEBAPP1 = 'https://domain1864238.ru/bots/3/web-apps/page1.php';
const WEBAPP11 = 'https://domain1864238.ru/bots/3/web-apps/page11.php';
const WEBAPP2 = 'https://domain1864238.ru/bots/3/web-apps/page2.php';

$db = [
    'host' => 'localhost',
    'user' => 'host1864238',
    'pass' => 'rSPrJw5xKZSf4gC',
    'db' => 'host1864238',
];

$dsn = "mysql:host={$db['host']};dbname={$db['db']};charset=utf8";
$opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$pdo = new PDO($dsn, $db['user'], $db['pass'], $opt);

