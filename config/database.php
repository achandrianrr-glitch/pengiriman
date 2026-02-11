<?php

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

class Database
{
    private mysqli $conn;

    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_OFF);

        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_errno) {
            throw new Exception('Koneksi database gagal: ' . $this->conn->connect_error);
        }

        if (!$this->conn->set_charset('utf8mb4')) {
            throw new Exception('Gagal mengatur charset database.');
        }
    }

    public function getConnection(): mysqli
    {
        return $this->conn;
    }

    private function detectTypes(array $params): string
    {
        $types = '';
        foreach ($params as $p) {
            if (is_int($p)) {
                $types .= 'i';
            } elseif (is_float($p)) {
                $types .= 'd';
            } elseif (is_null($p)) {
                $types .= 's';
            } elseif (is_bool($p)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }

    private function refValues(array &$arr): array
    {
        $refs = [];
        foreach ($arr as $k => &$v) {
            $refs[$k] = &$v;
        }
        return $refs;
    }

    public function query(string $sql, array $params = [], string $types = ''): mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Gagal prepare query.');
        }

        if (!empty($params)) {
            if ($types === '') {
                $types = $this->detectTypes($params);
            }

            $bindParams = array_merge([$types], $params);
            $refs = $this->refValues($bindParams);

            if (!call_user_func_array([$stmt, 'bind_param'], $refs)) {
                throw new Exception('Gagal bind parameter.');
            }
        }

        if (!$stmt->execute()) {
            throw new Exception('Gagal eksekusi query.');
        }

        return $stmt;
    }

    private function fetchAllAssoc(mysqli_stmt $stmt): array
    {
        $rows = [];

        $result = $stmt->get_result();
        if ($result !== false) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        }

        $meta = $stmt->result_metadata();
        if (!$meta) {
            return [];
        }

        $fields = $meta->fetch_fields();
        $row = [];
        $bind = [];

        foreach ($fields as $field) {
            $row[$field->name] = null;
            $bind[] = &$row[$field->name];
        }

        call_user_func_array([$stmt, 'bind_result'], $bind);

        while ($stmt->fetch()) {
            $rows[] = array_combine(array_keys($row), array_values($row));
        }

        return $rows;
    }

    public function select(string $sql, array $params = [], string $types = ''): array
    {
        $stmt = $this->query($sql, $params, $types);
        $data = $this->fetchAllAssoc($stmt);
        $stmt->close();
        return $data;
    }

    public function insert(string $sql, array $params = [], string $types = ''): int
    {
        $stmt = $this->query($sql, $params, $types);
        $stmt->close();
        return (int)$this->conn->insert_id;
    }

    public function update(string $sql, array $params = [], string $types = ''): int
    {
        $stmt = $this->query($sql, $params, $types);
        $affected = $stmt->affected_rows;
        $stmt->close();
        return (int)$affected;
    }

    public function delete(string $sql, array $params = [], string $types = ''): int
    {
        $stmt = $this->query($sql, $params, $types);
        $affected = $stmt->affected_rows;
        $stmt->close();
        return (int)$affected;
    }

    public function escape(string $value): string
    {
        return $this->conn->real_escape_string($value);
    }

    public function close(): void
    {
        $this->conn->close();
    }
}
