<?php
session_start();
require __DIR__.'/config/database.php';
if(isset($_SESSION['user_id'])){ header('Location: dashboard.php'); exit; }

$msg = '';
$msg_type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if(empty($full_name) || empty($username) || empty($email) || empty($password)){
        $msg = "⚠️ Please fill all fields";
        $msg_type = 'error';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $msg = "⚠️ Invalid Email Format";
        $msg_type = 'error';
    } elseif(strlen($password) < 6){
        $msg = "⚠️ Password must be 6+ characters";
        $msg_type = 'error';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
            $stmt->execute(['email' => $email, 'username' => $username]);
            
            if($stmt->fetch()){
                $msg = "⚠️ Email or Username already exists";
                $msg_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // KEY FIX: 4 columns = 4 values. No mismatch now
                $sql = "INSERT INTO users (username, full_name, email, password) VALUES (:username, :full_name, :email, :password)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'username' => $username,
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => $hashed_password
                ]);

                // Insert to expense_users also
                $pdo->prepare("INSERT INTO expense_users (full_name, email, password) VALUES (?, ?, ?)")
                    ->execute([$full_name, $email, $hashed_password]);

                $msg = "✅ Account Created Successfully! Redirecting...";
                $msg_type = 'success';
                header("refresh:2;url=login.php");
            }
        } catch(PDOException $e){
            $msg = "Database Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up | Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0a0a0a;
  --card:#111;
  --ink:#f5f5f5;
  --muted:#8a8a8a;
  --crimson:#dc143c;
  --crimson-light:#ff1744;
  --green:#22c55e;
  --line:#2a2a2a;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Outfit',system-ui,sans-serif}
body{
  background:var(--bg);
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:20px;
  background-image:
    radial-gradient(circle at 20% 20%, rgba(220,20,60,0.15) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(220,20,60,0.1) 0%, transparent 50%);
}
.wrap{width:100%;max-width:440px}
.logo{text-align:center;margin-bottom:30px}
.logo-icon{
  width:56px;height:56px;background:linear-gradient(135deg,var(--crimson),var(--crimson-light));
  border-radius:16px;display:inline-grid;place-items:center;font-weight:800;color:#fff;font-size:24px;
  box-shadow:0 8px 30px rgba(220,20,60,0.4);margin-bottom:12px
}
.logo b{font-size:20px;color:var(--ink);display:block}
.logo span{font-size:13px;color:var(--muted)}

.card{
  background:rgba(17,17,17,0.7);
  backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  border:1px solid var(--line);
  border-radius:20px;
  padding:36px;
  box-shadow:0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(220,20,60,0.1);
}
.card h2{font-size:28px;font-weight:800;letter-spacing:-0.8px;margin-bottom:8px;text-align:center}
.sub{font-size:14px;color:var(--muted);margin-bottom:28px;text-align:center}

.lab{display:block;font-size:12px;font-weight:600;color:var(--muted);margin:16px 0 8px;letter-spacing:0.3px;text-transform:uppercase}
.inp{
  width:100%;
  padding:14px 16px;
  background:#1a1a1a;
  border:1.5px solid var(--line);
  border-radius:12px;
  color:var(--ink);
  font-size:14px;
  outline:none;
  transition:0.2s
}
.inp:focus{
  border-color:var(--crimson);
  background:#222;
  box-shadow:0 0 0 4px rgba(220,20,60,0.15)
}
.inp::placeholder{color:#555}

.btn{
  width:100%;
  margin-top:24px;
  padding:15px;
  background:linear-gradient(180deg,var(--crimson),#a00028);
  color:white;
  border:none;
  border-radius:12px;
  font-weight:700;
  font-size:15px;
  cursor:pointer;
  transition:0.2s;
  box-shadow:0 8px 20px rgba(220,20,60,0.4)
}
.btn:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 28px rgba(220,20,60,0.6);
  background:linear-gradient(180deg,var(--crimson-light),var(--crimson));
}

.div{margin:24px 0;display:flex;align-items:center;gap:12px;color:var(--muted);font-size:12px}
.div::before,.div::after{content:'';flex:1;height:1px;background:var(--line)}

.footer{text-align:center;font-size:14px;color:var(--muted)}
.footer a{color:var(--crimson-light);text-decoration:none;font-weight:700}
.footer a:hover{text-decoration:underline}

.alert{
  padding:12px 14px;
  border-radius:10px;
  margin-bottom:20px;
  font-size:13px;
  font-weight:500;
  text-align:center
}
.alert.error{
  background:rgba(220,20,60,0.15);
  border:1px solid var(--crimson);
  color:#ff6b81;
}
.alert.success{
  background:rgba(34,197,94,0.15);
  border:1px solid var(--green);
  color:#4ade80;
}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <div class="logo-icon">S</div>
    <b>Student Expense Tracker</b>
    <span>Track smarter, spend better</span>
  </div>

  <div class="card">
    <h2>Create Account</h2>
    <div class="sub">Join us and start tracking your expenses</div>

    <?php if($msg): ?>
        <div class="alert <?= $msg_type ?>"><?= $msg ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <label class="lab">Full Name</label>
        <input type="text" name="full_name" class="inp" placeholder="Ammu Kannan" required>

        <label class="lab">Username</label>
        <input type="text" name="username" class="inp" placeholder="ammu_k" required>

        <label class="lab">Email Address</label>
        <input type="email" name="email" class="inp" placeholder="you@gmail.com" required>

        <label class="lab">Password</label>
        <input type="password" name="password" class="inp" placeholder="Minimum 6 characters" required>

        <button type="submit" class="btn">Sign Up Now →</button>
    </form>

    <div class="div">or</div>
    <div class="footer">
        Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>
</div>
</body>
</html>