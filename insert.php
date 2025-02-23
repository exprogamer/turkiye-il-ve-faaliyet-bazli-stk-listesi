<?php
$startTime = microtime(true);

$directory = 'path_to_json_files';
$dsn = "mysql:host=your_hostname;dbname=your_database_name;charset=utf8mb4";
$username = "your_username";
$password = "your_password";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $totalInserted = 0;

    foreach (glob("$directory/*.json") as $file) {
        $filename = basename($file);
        list($plakaNo, $ilWithExt) = explode('-', $filename, 2);
        $il = pathinfo($ilWithExt, PATHINFO_FILENAME);

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        $rows = [];
        if (isset($data['loadResult']['data']) && is_array($data['loadResult']['data'])) {
            foreach ($data['loadResult']['data'] as $record) {
                $rows[] = [
                    isset($record['siraNo']) ? $record['siraNo'] : null,
                    $plakaNo,
                    $il,
                    isset($record['nevi']) ? $record['nevi'] : null,
                    isset($record['altNevi']) ? $record['altNevi'] : null,
                    isset($record['kurumAdi']) ? $record['kurumAdi'] : null,
                    isset($record['webSite']) ? $record['webSite'] : null,
                    isset($record['kurumAdres']) ? $record['kurumAdres'] : null,
                    isset($record['telefon']) ? $record['telefon'] : null,
                    isset($record['kutukNo']) ? $record['kutukNo'] : null,
                    isset($record['loadResult']) ? $record['loadResult'] : null
                ];
            }
        }

        if (!empty($rows)) {
            // Build bulk insert query for the current file
            $placeholders = [];
            $values = [];
            foreach ($rows as $row) {
                $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $values = array_merge($values, $row);
            }
            $sql = "INSERT INTO derneklistesi 
                (ilSiraNo, plakaNo, il, nevi, altNevi, kurumAdi, webSite, kurumAdres, telefon, kutukNo, loadResult)
                VALUES " . implode(", ", $placeholders);

            $pdo->beginTransaction();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $pdo->commit();

            $count = count($rows);
            $totalInserted += $count;
            echo "File processed successfully [File: $filename, Records: $count]\n";
        } else {
            echo "No valid records in file [File: $filename]\n";
        }
    }

    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    echo "Operation was successful.\n Total records inserted: $totalInserted.\n Total execution time: {$executionTime} seconds.";
} catch (Exception $e) {
    echo "Operation failed: " . $e->getMessage();
}
