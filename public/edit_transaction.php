<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];
$id=$_GET['id']??0;
if(!$id){ header('Location: dashboard.php'); exit; }

$txn=null;
try{
  global $pdo;
  if(isset($pdo)){
    $stmt=$pdo->prepare("SELECT * FROM transactions WHERE id=? AND user_id=?");
    $stmt->execute([$id,$uid]);
    $txn=$stmt->fetch(PDO::FETCH_ASSOC);
  }
}catch(Exception $e){}
if(!$txn){ header('Location: dashboard.php'); exit; }

$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $type=trim($_POST['type']??'expense');
  $cat=trim($_POST['category']??'Other');
  $amt=floatval($_POST['amount']??0);
  $date=$_POST['date']??date('Y-m-d');
  $desc=trim($_POST['description']??'');

  if($amt<=0) $err="Please enter a valid amount.";
  else{
    try{
      global $pdo;
      $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
      $dateCol = 'date';
      if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
      elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
      elseif(!in_array('date',$cols) && in_array('created_at',$cols)) $dateCol='created_at';
      $sql = "UPDATE transactions SET type=?, category=?, amount=?, $dateCol=?, description=? WHERE id=? AND user_id=?";
      $stmt=$pdo->prepare($sql);
      $stmt->execute([$type,$cat,$amt,$date,$desc,$id,$uid]);
      header('Location: dashboard.php'); exit;
    }catch(Exception $e){ $err="Database error: ".$e->getMessage(); }
  }
}
function h($s){return htmlspecialchars($s??'');}
function sel($a,$b){return strtolower(trim($a))===strtolower(trim($b))?'selected':'';}
$dateVal = $txn['date']??$txn['transaction_date']??$txn['created_at']??date('Y-m-d');
$currCat = $txn['category']??'Other';
$currType = $txn['type']??'expense';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Transaction - Student Expense Tracker</title>
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
  padding:16px 28px;
  display:flex;
  justify-content:space-between;
  align-items:center
}
.top b{font-size:15px;font-weight:700;display:flex;gap:10px;align-items:center}
.logo-icon{
  width:30px;height:30px;
  background:linear-gradient(135deg,var(--crimson),var(--crimson-light));
  border-radius:8px;display:grid;place-items:center;color:#fff;font-weight:800;font-size:14px
}
.nav{display:flex;gap:24px;align-items:center}
.nav a{
  color:var(--muted);
  text-decoration:none;
  font-size:13.5px;
  font-weight:600;
  transition:0.2s
}
.nav a.active,.nav a:hover{color:var(--crimson-light)}

.wrap{max-width:580px;margin:0 auto;padding:40px 24px}
h1{
  font-size:36px;
  font-weight:800;
  letter-spacing:-0.5px;
  margin-bottom:28px;
  text-align:center;
  color:var(--crimson-light);
  text-shadow:0 0 20px rgba(220,20,60,0.3)
}

/* Card */
.card{
  background:rgba(17,17,17,0.7);
  backdrop-filter:blur(20px);
  border:1px solid var(--line);
  border-radius:16px;
  padding:28px;
  box-shadow:0 20px 60px rgba(0,0,0,0.5)
}

/* Toggle */
.tog{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
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
.tog input{accent-color:var(--crimson);width:18px;height:18px}
.tog label:has(input:checked){
  border-color:var(--crimson);
  background:rgba(220,20,60,0.15);
  color:var(--crimson-light);
  box-shadow:0 0 0 4px rgba(220,20,60,0.15)
}

.lab{display:block;font-size:12px;font-weight:700;margin:18px 0 10px;color:var(--muted);letter-spacing:0.5px;text-transform:uppercase}
.lab span{color:var(--muted);font-weight:400;text-transform:none}

/* Inputs */
.inp, .sel{
  width:100%;
  padding:13px 16px;
  background:#1a1a1a;
  border:1.5px solid var(--line);
  border-radius:10px;
  color:var(--ink);
  font-size:14px;
  outline:none;
  transition:0.2s
}
.inp:focus, .sel:focus{
  border-color:var(--crimson);
  background:#222;
  box-shadow:0 0 0 4px rgba(220,20,60,0.15)
}

/* Buttons */
.btns{display:flex;align-items:center;gap:16px;margin-top:24px}
.save{
  padding:13px 24px;
  background:linear-gradient(180deg,var(--crimson),#a00028);
  color:white;
  border:none;
  border-radius:10px;
  font-weight:700;
  font-size:14px;
  cursor:pointer;
  box-shadow:0 8px 20px rgba(220,20,60,0.4);
  transition:0.2s
}
.save:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 28px rgba(220,20,60,0.6)
}
.cancel{
  color:var(--muted);
  text-decoration:none;
  font-size:14px;
  font-weight:600;
  border-bottom:1px solid var(--line);
  padding-bottom:2px;
  transition:0.2s
}
.cancel:hover{color:var(--crimson-light);border-color:var(--crimson)}

/* Error */
.err{
  background:rgba(220,20,60,0.15);
  border:1px solid var(--crimson);
  color:#ff6b81;
  padding:12px;
  border-radius:10px;
  margin-bottom:18px;
  font-size:13px;
  font-weight:600
}
</style></head><body>
<div class="top"><b><div class="logo-icon">S</div> Student Expense Tracker</b><div class="nav"><a href="dashboard.php">Dashboard</a><a class="active" href="#">Edit transaction</a><a href="monthly_report.php">Monthly report</a><a href="logout.php">Log out</a></div></div>
<div class="wrap">
<h1>Edit transaction</h1>
<div class="card">
<?php if($err):?><div class="err"><?=h($err)?></div><?php endif;?>
<form method="POST">
<div class="tog">
<label><input type="radio" name="type" value="expense" <?= $currType!=='income'?'checked':''?> onchange="updateCats()"> 💸 Expense</label>
<label><input type="radio" name="type" value="income" <?= $currType==='income'?'checked':''?> onchange="updateCats()"> 💰 Income</label>
</div>

<label class="lab">Category</label>
<select name="category" id="cat" class="sel" required>
  <optgroup label="Daily Needs">
    <option <?=sel($currCat,'Food')?>>Food</option>
    <option <?=sel($currCat,'Mess / Canteen')?>>Mess / Canteen</option>
    <option <?=sel($currCat,'Transport / Bus')?>>Transport / Bus</option>
    <option <?=sel($currCat,'Accommodation / Rent')?>>Accommodation / Rent</option>
  </optgroup>
  <optgroup label="Study">
    <option <?=sel($currCat,'Education')?>>Education</option>
    <option <?=sel($currCat,'Books / Stationery')?>>Books / Stationery</option>
    <option <?=sel($currCat,'Internet / Recharge')?>>Internet / Recharge</option>
    <option <?=sel($currCat,'Course Fees')?>>Course Fees</option>
  </optgroup>
  <optgroup label="Lifestyle">
    <option <?=sel($currCat,'Travel')?>>Travel</option>
    <option <?=sel($currCat,'Entertainment / Movies')?>>Entertainment / Movies</option>
    <option <?=sel($currCat,'Shopping / Clothes')?>>Shopping / Clothes</option>
    <option <?=sel($currCat,'Health / Medical')?>>Health / Medical</option>
    <option <?=sel($currCat,'Gym / Sports')?>>Gym / Sports</option>
  </optgroup>
  <optgroup label="Others">
    <option <?=sel($currCat,'Bills / Electricity')?>>Bills / Electricity</option>
    <option <?=sel($currCat,'Personal Care')?>>Personal Care</option>
    <option <?=sel($currCat,'Other')?>>Other</option>
  </optgroup>
  <optgroup label="Income Sources">
    <option <?=sel($currCat,'Salary')?>>Salary</option>
    <option <?=sel($currCat,'Pocket Money')?>>Pocket Money</option>
    <option <?=sel($currCat,'Scholarship')?>>Scholarship</option>
    <option <?=sel($currCat,'Freelance / Part-time')?>>Freelance / Part-time</option>
    <option <?=sel($currCat,'From Parents')?>>From Parents</option>
    <option <?=sel($currCat,'Internship')?>>Internship</option>
    <option <?=sel($currCat,'Gift')?>>Gift</option>
  </optgroup>
</select>

<label class="lab">Amount (₹)</label>
<input type="number" step="0.01" min="1" name="amount" class="inp" value="<?=h($txn['amount']??'')?>" required>

<label class="lab">Date</label>
<input type="date" name="date" class="inp" value="<?=h(substr($dateVal,0,10))?>" required>

<label class="lab">Description <span>(optional)</span></label>
<input type="text" name="description" class="inp" value="<?=h($txn['description']??$txn['note']??'')?>" placeholder="e.g. Travel to college">

<div class="btns"><button type="submit" class="save">Update</button><a href="dashboard.php" class="cancel">Cancel</a></div>
</form>
</div>
</div>
<script>
function updateCats(){
 const type=document.querySelector('input[name=type]:checked').value;
 const sel=document.getElementById('cat');
 const curr="<?=h($currCat)?>";
 if(type==='income'){
  sel.innerHTML=`<optgroup label="Income Sources">
    <option>Salary</option><option>Pocket Money</option><option>Scholarship</option>
    <option>Freelance / Part-time</option><option>From Parents</option><option>Internship</option>
    <option>Gift</option><option>Other</option></optgroup>`;
 } else {
  sel.innerHTML=`<optgroup label="Daily Needs">
    <option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup>
  <optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup>
  <optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup>
  <optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>`;
 }
 // keep current if possible
 for(let o of sel.options){ if(o.text.toLowerCase()===curr.toLowerCase()){ o.selected=true; break; } }
}
// init based on current type
window.onload=()=>{ if("<?=$currType?>"==="income") updateCats(); };
</script>
</body></html>