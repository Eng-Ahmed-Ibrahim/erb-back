<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BackupDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $database;

    protected $username;

    protected $password;

    protected $host;

    protected $backupPath;

    public function __construct()
    {
        $this->database = env('DB_DATABASE');
        $this->username = env('DB_USERNAME');
        $this->password = env('DB_PASSWORD');
        $this->host = env('DB_HOST', '127.0.0.1');
        $this->backupPath = 'C:\backup';
    }

    // public function handle()
    // {
    //     if (!is_dir($this->backupPath)) {
    //         mkdir($this->backupPath, 0777, true);
    //     }

    //     $filename = 'backup-' . Carbon::now()->format('Y-m-d_H-i-s') . '.sql';
    //     $filePath = $this->backupPath . '\\' . $filename;

    //     $mysqli = new \mysqli($this->host, $this->username, $this->password, $this->database);
    //     if ($mysqli->connect_error) {
    //         Log::error('MySQL connection failed: ' . $mysqli->connect_error);
    //         return;
    //     }

    //     $backupContent = "CREATE DATABASE IF NOT EXISTS `{$this->database}`;\nUSE `{$this->database}`;\n\n";

    //     $mysqli->select_db($this->database);

    //     $tablesResult = $mysqli->query('SHOW TABLES');

    //     while ($row = $tablesResult->fetch_row()) {
    //         $table = $row[0];

    //         $createTableResult = $mysqli->query("SHOW CREATE TABLE `{$table}`");
    //         $createTableRow = $createTableResult->fetch_row();
    //         $backupContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
    //         $backupContent .= $createTableRow[1] . ";\n\n";

    //         $dataResult = $mysqli->query("SELECT * FROM `{$table}`");
    //         while ($dataRow = $dataResult->fetch_assoc()) {
    //             $columns = array_keys($dataRow);
    //             $values = array_map([$mysqli, 'real_escape_string'], array_values($dataRow));
    //             $backupContent .= "INSERT INTO `{$table}` (" . implode(", ", $columns) . ") VALUES ('" . implode("', '", $values) . "');\n";
    //         }
    //         $backupContent .= "\n\n";
    //     }

    //     file_put_contents($filePath, $backupContent);

    //     $mysqli->close();

    //     Log::info("Database backup completed successfully: {$filePath}");
    // }

    public function handle()
    {
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0777, true);
        }

        $filename = 'backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.sql';
        $filePath = $this->backupPath.'\\'.$filename;

        $mysqli = new \mysqli($this->host, $this->username, $this->password, $this->database);
        if ($mysqli->connect_error) {
            Log::error('MySQL connection failed: '.$mysqli->connect_error);

            return;
        }

        file_put_contents($filePath, "CREATE DATABASE IF NOT EXISTS `{$this->database}`;\nUSE `{$this->database}`;\n\n");

        $tablesResult = $mysqli->query('SHOW TABLES');

        while ($row = $tablesResult->fetch_row()) {
            $table = $row[0];

            // Get CREATE TABLE statement
            $createTableResult = $mysqli->query("SHOW CREATE TABLE `{$table}`");
            $createTableRow = $createTableResult->fetch_row();

            file_put_contents($filePath, "DROP TABLE IF EXISTS `{$table}`;\n".$createTableRow[1].";\n\n", FILE_APPEND);

            // Process data in chunks (reduce memory usage)
            $offset = 0;
            $chunkSize = 500;  // Adjust as needed

            do {
                $dataResult = $mysqli->query("SELECT * FROM `{$table}` LIMIT $chunkSize OFFSET $offset");
                $numRows = $dataResult->num_rows;

                while ($dataRow = $dataResult->fetch_assoc()) {
                    $columns = array_keys($dataRow);
                    $values = array_map([$mysqli, 'real_escape_string'], array_values($dataRow));

                    $insertStatement = "INSERT INTO `{$table}` (".implode(', ', $columns).") VALUES ('".implode("', '", $values)."');\n";
                    file_put_contents($filePath, $insertStatement, FILE_APPEND);
                }

                $offset += $chunkSize;
            } while ($numRows > 0);

            file_put_contents($filePath, "\n\n", FILE_APPEND);
        }

        $mysqli->close();

        Log::info("Database backup completed successfully: {$filePath}");
    }

    public function failed($e)
    {
        Log::error('Database backup failed: '.$e->getMessage());
    }
}
