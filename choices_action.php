<?php
session_start();

// 1. DATABASE CONNECTION
$host = '127.0.0.1';
$db   = 'myintern_db';
$user = 'root'; 
$pass = '';     

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// 2. HANDLE POST ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_choice') {
        $stmt = $pdo->query("SELECT MAX(choice_number) FROM companies");
        $nextNumber = ($stmt->fetchColumn() ?: 0) + 1;
        $supervisor_approval = $_POST['supervisor_approval'] ?? 'Pending';

        $stmt = $pdo->prepare("INSERT INTO companies (choice_number, company_name, company_nature, company_address, core_business, supervisor_approval) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nextNumber, $_POST['company_name'], $_POST['company_nature'], $_POST['company_address'], $_POST['core_business'], $supervisor_approval]);
        
        $_SESSION['message'] = "New company choice added successfully!";
    } 
    elseif ($action === 'edit_choice') {
        $stmt = $pdo->prepare("UPDATE companies SET company_name = ?, company_nature = ?, company_address = ?, core_business = ?, supervisor_approval = ? WHERE id = ?");
        $stmt->execute([$_POST['company_name'], $_POST['company_nature'], $_POST['company_address'], $_POST['core_business'], $_POST['supervisor_approval'], $_POST['choice_id']]);
        
        $_SESSION['message'] = "Company choice updated successfully!";
    } 
    elseif ($action === 'delete_choice') {
        $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
        $stmt->execute([$_POST['choice_id']]);
        
        // Re-sequence choice numbers after deletion
        $pdo->query("SET @count = 0");
        $pdo->query("UPDATE companies SET choice_number = @count:= @count + 1 ORDER BY choice_number ASC");

        $_SESSION['message'] = "Company choice deleted successfully!";
    }
    
    // Redirect back to the main page
    header("Location: index.php");
    exit;
}