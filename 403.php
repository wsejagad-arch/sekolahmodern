<?php
http_response_code(403);
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>403 - Akses Ditolak</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #111827;
            color: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .box {
            max-width: 680px;
            width: 90%;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 20px 45px rgba(0,0,0,0.25);
        }
        h1 {
            font-size: 2.5rem;
            margin: 0 0 16px;
            color: #fca5a5;
        }
        p {
            color: #d1d5db;
            font-size: 1.05rem;
            line-height: 1.7;
            margin: 0;
        }
        .danger {
            display: inline-block;
            margin-top: 18px;
            background: #ef4444;
            color: white;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>403</h1>
        <p>Jangan coba-coba!</p>
        <p>Area ini tidak terbuka untuk akses publik.</p>
        <div class="danger">Akses Ditolak</div>
    </div>
</body>
</html>
