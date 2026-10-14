<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];

// Fetch transactions - supports getTxns(), getTransactions(), or PDO
$txns = function_exists('getTxns') ? getTxns($uid) : (function_exists('getTransactions') ? getTransactions($uid) : []);
if(empty($txns)){
  try{
    global $pdo;
    if(isset($pdo)){
      $cols=$pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
      $dateCol='date';
      if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
      elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
      elseif(!in_array('date',$cols) && in_array('created_at',$cols)) $dateCol='created_at';
      $stmt=$pdo->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY $dateCol DESC");
      $stmt->execute([$uid]);
      $txns=$stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  }catch(Exception $e){}
}

// Month by month aggregation
$byMonth = [];
foreach($txns as $t){
  $d = $t['date']??$t['transaction_date']??$t['txn_date']??$t['created_at']??date('Y-m-d');
  $m = substr($d,0,7);
  $mName = date('F Y', strtotime($d));
  if(!isset($byMonth[$m])) $byMonth[$m]=['name'=>$mName,'income'=>0,'expense'=>0,'count'=>0];
  $amt=floatval($t['amount']??0);
  if(($t['type']??'expense')==='income') $byMonth[$m]['income']+=$amt;
  else $byMonth[$m]['expense']+=$amt;
  $byMonth[$m]['count']++;
}
krsort($byMonth);

// All-time by category
$byCat = [];
foreach($txns as $t){
  if(($t['type']??'expense')==='income') continue;
  $cat=$t['category']??'Other';
  if(!isset($byCat[$cat])) $byCat[$cat]=['entries'=>0,'total'=>0];
  $byCat[$cat]['entries']++;
  $byCat[$cat]['total']+=floatval($t['amount']??0);
}
arsort($byCat);

$totalInc=array_sum(array_column($byMonth,'income'));
$totalExp=array_sum(array_column($byMonth,'expense'));
$totalSaved=$totalInc-$totalExp;
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Monthly Report - Student Expense Tracker</title>
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
  --gold:#f5d76e;
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
  background:rgba(17,17,17,0.8);
  backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  border-bottom:2px solid var(--crimson);
  padding:16px 28px;
  display:flex;
  justify-content:space-between;
  align-items:center
}
.top b{font-size:15px;display:flex;gap:10px;align-items:center;font-weight:700}
.logo-icon{
  width:32px;height:32px;
  background:linear-gradient(135deg,var(--crimson),var(--crimson-light));
  border-radius:8px;display:grid;place-items:center;color:#fff;font-weight:800;font-style:normal
}
.nav{display:flex;gap:24px;align-items:center}
.nav a{
  color:var(--muted);
  text-decoration:none;
  font-size:14px;
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

.wrap{max-width:900px;margin:0 auto;padding:40px 24px}
h1{
  font-size:44px;
  font-weight:800;
  letter-spacing:-1.5px;
  margin-bottom:8px;
  color:var(--crimson-light);
  text-shadow:0 0 20px rgba(220,20,60,0.3)
}
.sub{font-size:14px;color:var(--muted);margin-bottom:32px}

/* Stats Grid */
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px}
.stat{
  background:rgba(17,17,17,0.7);
  backdrop-filter:blur(20px);
  border:1px solid var(--line);
  border-radius:16px;
  padding:20px;
  position:relative;
  overflow:hidden;
  transition:0.3s
}
.stat:hover{border-color:var(--crimson);transform:translateY(-2px)}
.stat::before{
  content:'';
  position:absolute;
  left:0;
  top:0;
  bottom:0;
  width:4px
}
.stat:nth-child(1)::before{background:var(--green)}
.stat:nth-child(2)::before{background:var(--crimson)}
.stat:nth-child(3)::before{background:var(--gold)}
.stat small{
  font-size:11px;
  letter-spacing:1px;
  color:var(--muted);
  font-weight:700;
  text-transform:uppercase
}
.stat h2{
  font-size:28px;
  font-weight:800;
  letter-spacing:-1px;
  margin-top:10px
}

/* Box */
.box{
  background:rgba(17,17,17,0.7);
  backdrop-filter:blur(20px);
  border:1px solid var(--line);
  border-radius:20px;
  padding:28px;
  margin-bottom:24px;
  box-shadow:0 20px 60px rgba(0,0,0,0.4)
}
.box h3{
  font-size:15px;
  font-weight:700;
  margin-bottom:20px;
  display:flex;
  align-items:center;
  gap:10px;
  color:var(--ink)
}
.box h3 span{
  width:32px;
  height:32px;
  background:#1a1a1a;
  border:1px solid var(--line);
  border-radius:8px;
  display:grid;
  place-items:center;
  font-size:14px
}

/* Table */
table{width:100%;border-collapse:collapse}
th{
  font-size:11px;
  letter-spacing:1px;
  color:var(--muted);
  text-transform:uppercase;
  font-weight:700;
  text-align:left;
  padding:12px 10px;
  border-bottom:1px solid var(--line)
}
th:nth-child(2),th:nth-child(3),th:nth-child(4){text-align:right}
td{
  padding:16px 10px;
  border-bottom:1px solid #1a1a1a;
  font-size:14px
}
td:nth-child(2),td:nth-child(3),td:nth-child(4){text-align:right}

.inc{color:var(--green);font-weight:700}
.exp{color:var(--crimson);font-weight:700}
.saved{
  color:var(--gold);
  font-weight:700;
  background:rgba(245,215,110,0.1);
  padding:5px 12px;
  border-radius:20px;
  font-size:13px
}

.cat-bar{
  height:8px;
  background:#1a1a1a;
  border-radius:10px;
  margin-top:10px;
  overflow:hidden
}
.cat-bar i{
  display:block;
  height:100%;
  background:linear-gradient(90deg,var(--crimson),var(--crimson-light));
  border-radius:10px;
  box-shadow:0 0 10px rgba(220,20,60,0.4)
}

.empty{padding:32px;text-align:center;color:var(--muted);font-size:14px}

@media(max-width:700px){
  .grid{grid-template-columns:1fr}
  .wrap{padding:24px 16px}
  h1{font-size:34px}
  .nav{gap:16px}
</style></head><body>
<div class="top">
  <b><div class="logo-icon">S</div> Student Expense Tracker</b>
  <div class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="add_transaction.php">Add transaction</a>
    <a class="active" href="monthly_report.php">Monthly report</a>
    <a href="logout.php">Log out</a>
  </div>
</div>

<div class="wrap">
<h1>Monthly Report</h1>
<div class="sub">Analyze your spending trends and category breakdown.</div>

<div class="grid">
  <div class="stat"><small>Total Income</small><h2>₹<?=number_format($totalInc)?></h2></div>
  <div class="stat"><small>Total Expenses</small><h2>₹<?=number_format($totalExp)?></h2></div>
  <div class="stat"><small>Total Saved</small><h2>₹<?=number_format($totalSaved)?></h2></div>
</div>

<div class="box">
<h3><span>📅</span> Month by month</h3>
<table>
<tr><th>Month</th><th>Income</th><th>Expenses</th><th>Saved</th></tr>
<?php if(!$byMonth):?>
<tr><td colspan="4" class="empty">No transactions found. Start by adding your first transaction.</td></tr>
<?php else: foreach($byMonth as $data): $saved=$data['income']-$data['expense']; ?>
<tr>
<td style="font-weight:600"><?=h($data['name'])?></td>
<td class="inc">₹<?=number_format($data['income'])?></td>
<td class="exp">₹<?=number_format($data['expense'])?></td>
<td><span class="saved">₹<?=number_format($saved)?></span></td>
</tr>
<?php endforeach; endif; ?>
</table>
</div>

<div class="box">
<h3><span>📊</span> All-time expenses by category</h3>
<table>
<tr><th>Category</th><th>Entries</th><th>Total</th></tr>
<?php if(!$byCat):?>
<tr><td colspan="3" class="empty">No expenses recorded yet.</td></tr>
<?php else: 
  $max = $byCat ? max(array_column($byCat,'total')) : 1;
  foreach($byCat as $cat=>$info): $pct=$max?($info['total']/$max*100):0;
?>
<tr>
<td>
  <div style="font-weight:600"><?=h($cat)?></div>
  <div class="cat-bar"><i style="width:<?=$pct?>%"></i></div>
</td>
<td style="text-align:right;color:var(--muted)"><?=h($info['entries'])?></td>
<td style="text-align:right;font-weight:700">₹<?=number_format($info['total'])?></td>
</tr>
<?php endforeach; endif; ?>
</table>
</div>

</div>
</body></html>