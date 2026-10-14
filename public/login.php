<?php
session_start();
require __DIR__.'/config/database.php';
if(isset($_SESSION['user_id'])){ header('Location: dashboard.php'); exit; }

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $email=trim($_POST['email']??'');
 $pass=$_POST['password']??'';
 if($email && $pass){
  $u = function_exists('getUserByEmail')? getUserByEmail($email) : null;
  if(!$u){
    try{
      global $pdo;
      if(isset($pdo)){
        $stmt=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $u=$stmt->fetch(PDO::FETCH_ASSOC);
      }
    }catch(Exception $e){}
  }
  if($u && password_verify($pass,$u['password'])){
   session_regenerate_id(true);
   $_SESSION['user_id']=$u['id'];
   $_SESSION['user_name']=$u['name']??$u['username']??explode('@',$u['email'])[0];
   header('Location: dashboard.php'); exit;
  } else {
    $error="Invalid email or password. Please try again.";
  }
 } else {
   $error="Please fill in all fields.";
 }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log in - Student Expense Tracker</title>
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

.pass-wrap{position:relative}
.eye{
  position:absolute;
  right:12px;
  top:50%;
  transform:translateY(-50%);
  background:none;
  border:none;
  cursor:pointer;
  color:var(--muted);
  font-size:18px;
  width:32px;
  height:32px;
  display:grid;
  place-items:center;
  border-radius:8px;
  transition:0.2s
}
.eye:hover{background:#222;color:var(--crimson-light)}

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

.foot{text-align:center;font-size:14px;color:var(--muted)}
.foot a{color:var(--crimson-light);text-decoration:none;font-weight:700}
.foot a:hover{text-decoration:underline}

.error{
  background:rgba(220,20,60,0.15);
  border:1px solid var(--crimson);
  color:#ff6b81;
  padding:12px 14px;
  border-radius:10px;
  margin-bottom:20px;
  font-size:13px;
  font-weight:500
}
</style>
</head><body>
<div class="wrap">
  <div class="logo">
    <div class="logo-icon">S</div>
    <b>Student Expense Tracker</b>
    <span>Track smarter, spend better</span>
  </div>

  <div class="card">
    <h2>Welcome Back</h2>
    <div class="sub">Sign in to continue to your dashboard</div>

    <?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>

    <form method="POST" autocomplete="off">
      <label class="lab">Email Address</label>
      <input type="email" name="email" class="inp" required placeholder="you@college.edu" value="" autocomplete="off">

      <label class="lab">Password</label>
      <div class="pass-wrap">
        <input type="password" id="password" name="password" class="inp" required placeholder="Enter your password" value="" autocomplete="new-password">
        <button type="button" class="eye" onclick="togglePass()" id="eyeBtn">👁</button>
      </div>

      <button type="submit" class="btn">Log in →</button>
    </form>

    <div class="div">or</div>
    <div class="foot">New here? <a href="register.php">Create an account</a></div>
  </div>
</div>

<script>
function togglePass(){
  const inp = document.getElementById('password');
  const btn = document.getElementById('eyeBtn');
  if(inp.type === 'password'){
    inp.type = 'text';
    btn.textContent = '🙈';
  } else {
    inp.type = 'password';
    btn.textContent = '👁';
  }
}
</script>
</body></html>