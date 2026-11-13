<?php
// 1. DATABASE CONNECTION
$host = '127.0.0.1';
$db   = 'myintern_db';
$user = 'root'; // Update for your lab environment
$pass = '';     // Update for your lab environment

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// 2. HANDLE ACTIONS (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_choice') {
        // Auto-increment the choice number
        $stmt = $pdo->query("SELECT MAX(choice_number) FROM companies");
        $nextNumber = ($stmt->fetchColumn() ?: 0) + 1;

        // Default to Pending since the field is disabled in the form[cite: 2]
        $supervisor_approval = $_POST['supervisor_approval'] ?? 'Pending';

        $stmt = $pdo->prepare("INSERT INTO companies (choice_number, company_name, company_nature, company_address, core_business, supervisor_approval) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nextNumber,
            $_POST['company_name'],
            $_POST['company_nature'],
            $_POST['company_address'],
            $_POST['core_business'],
            $supervisor_approval
        ]);
    } 
    elseif ($action === 'edit_choice') {
        $stmt = $pdo->prepare("UPDATE companies SET company_name = ?, company_nature = ?, company_address = ?, core_business = ?, supervisor_approval = ? WHERE id = ?");
        $stmt->execute([
            $_POST['company_name'],
            $_POST['company_nature'],
            $_POST['company_address'],
            $_POST['core_business'],
            $_POST['supervisor_approval'], // Captured from a hidden input since radios are disabled
            $_POST['choice_id']
        ]);
    } 
    elseif ($action === 'delete_choice') {
        $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
        $stmt->execute([$_POST['choice_id']]);
        
        // Re-sequence choice numbers after deletion[cite: 2]
        $pdo->query("SET @count = 0");
        $pdo->query("UPDATE companies SET choice_number = @count:= @count + 1 ORDER BY choice_number ASC");
    }
    
    header("Location: index.php");
    exit;
}

// 3. FETCH DATA
$choices = $pdo->query("SELECT * FROM companies ORDER BY choice_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SECTION B: LIST OF CHOICES</title>
    <style>
        :root {
            --primary: #0056b3;
            --bg-color: #f8f9fa;
            --border-color: #dee2e6;
        }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: var(--bg-color); 
            padding: 20px; 
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h2, h3 {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .card { 
            background: white; 
            margin-bottom: 20px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header {
            background: #e9ecef;
            padding: 10px 20px;
            font-size: 1.2em;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        .choice-badge {
            background: var(--primary);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2em;
        }
        .card-body {
            padding: 20px;
        }
        .form-group {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 15px;
            margin-bottom: 15px;
            align-items: start;
        }
        .form-label {
            font-weight: 600;
            padding-top: 8px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-sizing: border-box;
            font-family: inherit;
        }
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-top: 8px;
        }
        .controls { 
            padding: 15px 20px; 
            background: #f8f9fa; 
            border-top: 1px solid var(--border-color);
            text-align: right; 
        }
        .btn { 
            padding: 8px 16px; 
            cursor: pointer; 
            border: none;
            border-radius: 4px;
            font-weight: bold;
            transition: 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #004494; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .disabled-section {
            background: #f1f3f5;
            padding: 10px;
            border-radius: 4px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-group {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            .form-label {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>SECTION B: LIST OF CHOICES</h2>

        <!-- SECTION 1: ADD NEW CHOICE FORM -->
        <div class="card" style="border: 2px dashed var(--primary);">
            <div class="card-header">
                <span class="choice-badge">+</span> Add New Company
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_choice">
                    
                    <div class="form-group">
                        <div class="form-label">Name of company</div>
                        <div><input type="text" name="company_name" class="form-control" required></div>
                    </div>

                    <div class="form-group">
                        <div class="form-label">Nature of company</div>
                        <div class="radio-group">
                            <label><input type="radio" name="company_nature" value="Government" required> Government</label>
                            <label><input type="radio" name="company_nature" value="Private"> Private</label>
                            <label><input type="radio" name="company_nature" value="Multinational"> Multinational</label>
                            <label><input type="radio" name="company_nature" value="Others"> Others</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-label">Address of company</div>
                        <div><textarea name="company_address" class="form-control" rows="3" required></textarea></div>
                    </div>

                    <div class="form-group">
                        <div class="form-label">What is the core business of the company</div>
                        <div><textarea name="core_business" class="form-control" rows="2" required></textarea></div>
                    </div>

                    <div class="form-group disabled-section">
                        <div class="form-label">Academic Supervisor’s Approval<br><small style="font-weight:normal; color:#666;">(For supervisor use only)</small></div>
                        <div class="radio-group">
                            <input type="hidden" name="supervisor_approval" value="Pending">
                            <label><input type="radio" disabled> Highly Recommended</label>
                            <label><input type="radio" disabled> Recommended</label>
                            <label><input type="radio" disabled> Not Recommended</label>
                            <label><input type="radio" checked disabled> <i>Pending</i></label>
                        </div>
                    </div>

                    <div class="controls" style="text-align: left; background: transparent; padding: 10px 0 0 0; border: none;">
                        <button type="submit" class="btn btn-primary">Add New Choice</button>
                    </div>
                </form>
            </div>
        </div>

        <h3 style="margin-top: 40px;">Added Companies</h3>

        <!-- SECTION 2: DISPLAY EXISTING CHOICES -->
        <?php if(empty($choices)): ?>
            <p>No companies added yet. Please add your choices above.</p>
        <?php endif; ?>

        <?php foreach ($choices as $choice): ?>
            <div class="card">
                <div class="card-header">
                    <span class="choice-badge"><?= $choice['choice_number'] ?></span>
                    <?= htmlspecialchars($choice['company_name']) ?>
                </div>
                <form method="POST">
                    <div class="card-body">
                        <input type="hidden" name="action" value="edit_choice">
                        <input type="hidden" name="choice_id" value="<?= $choice['id'] ?>">
                        
                        <div class="form-group">
                            <div class="form-label">Name of company</div>
                            <div><input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($choice['company_name']) ?>" required></div>
                        </div>

                        <div class="form-group">
                            <div class="form-label">Nature of company</div>
                            <div class="radio-group">
                                <label><input type="radio" name="company_nature" value="Government" <?= $choice['company_nature'] == 'Government' ? 'checked' : '' ?>> Government</label>
                                <label><input type="radio" name="company_nature" value="Private" <?= $choice['company_nature'] == 'Private' ? 'checked' : '' ?>> Private</label>
                                <label><input type="radio" name="company_nature" value="Multinational" <?= $choice['company_nature'] == 'Multinational' ? 'checked' : '' ?>> Multinational</label>
                                <label><input type="radio" name="company_nature" value="Others" <?= $choice['company_nature'] == 'Others' ? 'checked' : '' ?>> Others</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-label">Address of company</div>
                            <div><textarea name="company_address" class="form-control" rows="3"><?= htmlspecialchars($choice['company_address']) ?></textarea></div>
                        </div>

                        <div class="form-group">
                            <div class="form-label">What is the core business of the company</div>
                            <div><textarea name="core_business" class="form-control" rows="2"><?= htmlspecialchars($choice['core_business']) ?></textarea></div>
                        </div>

                        <div class="form-group disabled-section">
                            <div class="form-label">Academic Supervisor’s Approval</div>
                            <div class="radio-group">
                                <!-- Hidden input preserves the value since disabled radios don't submit -->
                                <input type="hidden" name="supervisor_approval" value="<?= htmlspecialchars($choice['supervisor_approval']) ?>">
                                
                                <label><input type="radio" disabled <?= $choice['supervisor_approval'] == 'Highly Recommended' ? 'checked' : '' ?>> Highly Recommended</label>
                                <label><input type="radio" disabled <?= $choice['supervisor_approval'] == 'Recommended' ? 'checked' : '' ?>> Recommended</label>
                                <label><input type="radio" disabled <?= $choice['supervisor_approval'] == 'Not Recommended' ? 'checked' : '' ?>> Not Recommended</label>
                                <label><input type="radio" disabled <?= $choice['supervisor_approval'] == 'Pending' ? 'checked' : '' ?>> <i>Pending</i></label>
                            </div>
                        </div>
                    </div>
                    <div class="controls">
                        <button type="submit" class="btn btn-primary">Update Choice</button>
                        <button type="submit" name="action" value="delete_choice" class="btn btn-danger" onclick="return confirm('Delete this choice?');">Delete</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>