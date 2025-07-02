<?php
$host = 'localhost';
$dbname = 'lms';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


function executeQuery($conn, $query, $params = [], $types = "")
{
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['success' => false, 'error' => $conn->error];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $result = $stmt->execute();
    if (!$result) {
        return ['success' => false, 'error' => $stmt->error];
    }

    $data = $stmt->get_result();
    $stmt->close();

    return ['success' => true, 'data' => $data];
}


function getUserByEmail($conn, $email)
{
    $query = "SELECT * FROM users WHERE email = ?";
    $result = executeQuery($conn, $query, [$email], "s");

    if ($result['success'] && $result['data']->num_rows > 0) {
        return $result['data']->fetch_assoc();
    }
    return null;
}

function getUserById($conn, $id)
{
    $query = "SELECT * FROM users WHERE id = ?";
    $result = executeQuery($conn, $query, [$id], "i");

    if ($result['success'] && $result['data']->num_rows > 0) {
        return $result['data']->fetch_assoc();
    }
    return null;
}