<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];
$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
 $type=trim($_POST['type']??'expense');
 $cat=trim($_POST['category']??'Food');
 $amt=floatval($_POST['amount']??0);
 $date=$_POST['date']??date('Y-m-d');
 $desc=trim($_POST['description']??'');

 if($amt<=0) $err="Please enter a valid amount greater than zero.";
 else{
  if(function_exists('addTransaction')) {
    try{
      addTransaction($uid,$type,$cat,$amt,$date,$desc);
    }catch(Exception $e){ $err="Failed to save: ".$e->getMessage(); }
  } else {
    try{
      global $pdo;
      if(isset($pdo)){
        $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
        $dateCol = 'date';
        if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
        elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
        elseif(in_array('trans_date',$cols)) $dateCol='trans_date';
        elseif(in_array('created_at',$cols) && !in_array('date',$cols)) $dateCol='created_at';
        
        $sql = "INSERT INTO transactions (user_id,type,category,amount,$dateCol,description) VALUES (?,?,?,?,?,?)";
        $stmt=$pdo->prepare($sql);
        $stmt->execute([$uid,$type,$cat,$amt,$date,$desc]);
      }
    }catch(Exception $e){ 
      $err="Database error: ".$e->getMessage(); 
    }
  }
  if(!$err){ header('Location: dashboard.php'); exit; }
 }
}
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add transaction - Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0a0a0a;
  --card:#111;
  --ink:#f5f5f5;
  --muted:#8a8a8a;
  --crimson:#dc143c;
  --crimson-light:#ff1744;
  --line:#2a2a2a;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Outfit',system-ui,sans-serif}
body{
  background:var(--bg);
  color:var(--ink);
  min-height:100vh;
  background-image:
    radial-gradient(circle at 20% 20%, rgba(220,20,60,0.08) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(220,20,60,0.05) 0%, transparent 50%);
}

/* Navbar */
.top{
  position:sticky;
  top:0;
  z-index:10;
  background:rgba(17,17,17,0.92);
  backdrop-filter:blur(20px);
  border-bottom:2px solid var(--crimson);
  padding:14px 28px;
  display:flex;
  justify-content:space-between;
  align-items:center
}
.top b{font-size:14px;display:flex;align-items:center;gap:10px;letter-spacing:-0.2px;font-weight:700}
.top b i{
  width:30px;
  height:30px;
  background:linear-gradient(135deg,var(--crimson),var(--crimson-light));
  border-radius:8px;
  display:grid;
  place-items:center;
  color:#fff;
  font-weight:800;
  font-style:normal
}
.nav{display:flex;gap:24px;align-items:center}
.nav a{
  color:var(--muted);
  text-decoration:none;
  font-size:13px;
  font-weight:600;
  position:relative;
  padding-bottom:3px;
  transition:0.2s
}
.nav a.active,.nav a:hover{color:var(--crimson-light)}
.nav a.active::after{
  content:'';
  position:absolute;
  left:0;
  right:0;
  bottom:-8px;
  height:2px;
  background:var(--crimson-light);
  border-radius:2px
}

.wrap{max-width:720px;margin:0 auto;padding:40px 24px}
h1{
  font-size:44px;
  font-weight:800;
  letter-spacing:-1.5px;
  text-align:center;
  margin-bottom:10px;
  color:var(--crimson-light);
  text-shadow:0 0 20px rgba(220,20,60,0.3)
}
.sub-h{text-align:center;color:var(--muted);font-size:13.5px;margin-bottom:28px}

/* Card */
.card{
  max-width:520px;
  margin:0 auto;
  background:rgba(17,17,17,0.7);
  backdrop-filter:blur(20px);
  border:1px solid var(--line);
  border-radius:20px;
  padding:28px;
  box-shadow:0 20px 60px rgba(0,0,0,0.5)
}

/* Toggle */
.tog{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px}
.tog label{
  background:#1a1a1a;
  border:2px solid var(--line);
  border-radius:12px;
  padding:14px 16px;
  display:flex;
  align-items:center;
  gap:10px;
  cursor:pointer;
  font-size:13px;
  font-weight:700;
  color:var(--muted);
  transition:0.2s
}
.tog input{accent-color:var(--crimson);width:16px;height:16px}
.tog label:has(input:checked){
  border-color:var(--crimson);
  background:rgba(220,20,60,0.15);
  color:var(--crimson-light);
  box-shadow:0 0 0 4px rgba(220,20,60,0.15)
}

.lab{display:block;font-size:12px;font-weight:700;margin:20px 0 8px;color:var(--muted);letter-spacing:0.5px;text-transform:uppercase}
.lab span{color:var(--muted);font-weight:400;text-transform:none}

/* Inputs */
.inp,.sel{
  width:100%;
  padding:14px 16px;
  background:#1a1a1a;
  border:1.5px solid var(--line);
  border-radius:12px;
  color:var(--ink);
  font-size:14px;
  outline:none;
  transition:0.2s;
  appearance:none
}
.inp:focus,.sel:focus{
  border-color:var(--crimson);
  background:#222;
  box-shadow:0 0 0 4px rgba(220,20,60,0.2)
}
.inp::placeholder{color:#555}

/* Buttons */
.btns{display:flex;align-items:center;gap:16px;margin-top:28px}
.save{
  padding:13px 24px;
  background:linear-gradient(180deg,var(--crimson),#a00028);
  color:white;
  border:none;
  border-radius:12px;
  font-weight:700;
  font-size:13.5px;
  cursor:pointer;
  box-shadow:0 8px 20px rgba(220,20,60,0.4);
  transition:0.2s
}
.save:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 28px rgba(220,20,60,0.6)
}
.cancel{
  font-size:13px;
  color:var(--muted);
  text-decoration:none;
  border-bottom:1px solid var(--line);
  padding-bottom:2px;
  transition:0.2s;
  font-weight:600
}
.cancel:hover{color:var(--crimson-light);border-color:var(--crimson)}

/* Error */
.err{
  background:rgba(220,20,60,0.15);
  border:1px solid var(--crimson);
  color:#ff6b81;
  padding:12px 14px;
  border-radius:10px;
  margin-bottom:18px;
  font-size:13px;
  font-weight:600
}
</style></head><body>
<div class="top"><b><i>S</i> Student Expense Tracker</b><div class="nav"><a href="dashboard.php">Dashboard</a><a class="active" href="add_transaction.php">Add transaction</a><a href="monthly_report.php">Monthly report</a><a href="logout.php">Log out</a></div></div>
<div class="wrap">
<h1>Add transaction</h1>
<div class="sub-h">Record your income or expense in seconds</div>
<div class="card">
<?php if($err):?><div class="err"><?=h($err)?></div><?php endif;?>
<form method="POST">
<div class="tog">
<label><input type="radio" name="type" value="expense" checked onchange="updateCats()"> 💸 Expense</label>
<label><input type="radio" name="type" value="income" onchange="updateCats()"> 💰 Income</label>
</div>

<label class="lab">Category</label>
<select name="category" id="cat" class="sel" required>
  <optgroup label="Daily Needs"><option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup>
  <optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup>
  <optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup>
  <optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>
</select>

<label class="lab">Amount (₹)</label>
<input type="number" step="0.01" min="1" name="amount" class="inp" placeholder="e.g. 500" required>

<label class="lab">Date</label>
<input type="date" name="date" class="inp" value="<?=date('Y-m-d')?>" required>

<label class="lab">Description <span>(optional)</span></label>
<input type="text" name="description" class="inp" placeholder="e.g. Mess fees, bus pass">

<div class="btns"><button type="submit" class="save">Save transaction</button><a href="dashboard.php" class="cancel">Cancel</a></div>
</form>
</div>
</div>

<script>
function updateCats(){
 const type=document.querySelector('input[name=type]:checked').value;
 const sel=document.getElementById('cat');
 if(type==='income'){
  sel.innerHTML='<optgroup label="Income Sources"><option>Salary</option><option>Pocket Money</option><option>Scholarship</option><option>Freelance / Part-time</option><option>From Parents</option><option>Internship</option><option>Gift</option><option>Other</option></optgroup>';
 } else {
  sel.innerHTML='<optgroup label="Daily Needs"><option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup><optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup><optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup><optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>';
 }
}
</script>
</body></html>