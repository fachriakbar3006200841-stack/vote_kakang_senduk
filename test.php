<?php
$phpVersion = PHP_VERSION;
$pdoMysqlLoaded = extension_loaded('pdo_mysql');
$pdoLoaded = extension_loaded('pdo');
$mysqlndLoaded = extension_loaded('mysqlnd');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Test</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; padding: 32px; }
        .box { max-width: 760px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 10px 24px rgba(0,0,0,.08); }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 10px 12px; text-align: left; }
        th { background: #eef2ff; }
        .pass { color: #166534; font-weight: bold; }
        .fail { color: #991b1b; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Uji PHP & PDO MySQL</h1>
        <table>
            <tr>
                <th>Item</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Versi PHP</td>
                <td><?= htmlspecialchars($phpVersion) ?></td>
            </tr>
            <tr>
                <td>PDO</td>
                <td class="<?= $pdoLoaded ? 'pass' : 'fail' ?>"><?= $pdoLoaded ? 'PASS' : 'FAIL' ?></td>
            </tr>
            <tr>
                <td>PDO MySQL</td>
                <td class="<?= $pdoMysqlLoaded ? 'pass' : 'fail' ?>"><?= $pdoMysqlLoaded ? 'PASS' : 'FAIL' ?></td>
            </tr>
            <tr>
                <td>mysqlnd</td>
                <td class="<?= $mysqlndLoaded ? 'pass' : 'fail' ?>"><?= $mysqlndLoaded ? 'PASS' : 'FAIL' ?></td>
            </tr>
        </table>
        <p>Ini adalah file pengujian awal untuk memastikan PHP berjalan melalui Apache dan ekstensi PDO MySQL tersedia.</p>
    </div>
</body>
</html>
