<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$uname = $_SESSION['user_name'] ?? 'User';

$filterMonth = $_GET['month'] ?? date('Y-m');
$filterCat = $_GET['category'] ?? 'All';
$search = trim($_GET['search'] ?? '');

$transactions = [];
try{
  global $pdo;
  if(isset($pdo)){
    $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
    $dateCol = in_array('date',$cols)?'date':(in_array('transaction_date',$cols)?'transaction_date':'created_at');
    
    $sql = "SELECT * FROM transactions WHERE user_id=? ";
    $params = [$uid];
    if($filterMonth){
      $sql .= " AND DATE_FORMAT($dateCol, '%Y-%m') = ? ";
      $params[] = $filterMonth;
    }
    $sql .= " ORDER BY $dateCol DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}catch(Exception $e){}

// Filter by category & search in PHP
if($filterCat!=='All' && $filterCat!==''){
  $transactions = array_filter($transactions, fn($t)=>strtolower($t['category']??'')===strtolower($filterCat));
}
if($search!==''){
  $transactions = array_filter($transactions, fn($t)=>stripos(($t['description']??'').($t['category']??''), $search)!==false);
}

$totalIncome = 0; $totalExpense = 0;
$byCat = [];
foreach($transactions as $t){
  $amt = floatval($t['amount']??0);
  if(($t['type']??'expense')==='income') $totalIncome += $amt;
  else { $totalExpense += $amt; $byCat[$t['category']??'Other'] = ($byCat[$t['category']??'Other']??0)+$amt; }
}
$balance = $totalIncome - $totalExpense;
$maxCat = $byCat ? max($byCat) : 1;
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - Student Expense Tracker</title>
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

/* NEW LAYOUT: Sidebar + Main */
.container{display:flex;min-height:100vh}
.sidebar{
  width:260px;
  background:rgba(17,17,17,0.8);
  backdrop-filter:blur(20px);
  border-right:2px solid var(--crimson);
  padding:24px 20px;
  position:sticky;
  top:0;
  height:100vh;
  display:flex;
  flex-direction:column;
  gap:30px
}
.brand{display:flex;gap:10px;align-items:center;font-weight:800;font-size:16px}
.logo-icon{
  width:36px;height:36px;
  background:linear-gradient(135deg,var(--crimson),var(--crimson-light));
  border-radius:10px;display:grid;place-items:center;color:#fff;font-weight:800;font-size:15px
}
.sidenav{display:flex;flex-direction:column;gap:6px}
.sidenav a{
  color:var(--muted);
  text-decoration:none;
  font-size:14px;
  font-weight:600;
  padding:12px 14px;
  border-radius:10px;
  transition:0.2s;
  display:flex;
  gap:10px;
  align-items:center
}
.sidenav a.active,.sidenav a:hover{
  background:rgba(220,20,60,0.15);
  color:var(--crimson-light);
  border-left:3px solid var(--crimson)
}
.user-info{margin-top:auto;padding:16px;background:#1a1a1a;border-radius:12px;border:1px solid var(--line)}
.user-info small{color:var(--muted);font-size:11px}
.user-info b{font-size:13px}

.main{flex:1;padding:32px 40px;overflow-y:auto}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:16px}
.header h1{
  font-size:36px;
  font-weight:800;
  letter-spacing:-0.6px;
  color:var(--crimson-light);
  text-shadow:0 0 20px rgba(220,20,60,0.3)
}
.header p{font-size:13px;color:var(--muted);margin-top:6px}
.reset{
  font-size:12px;
  padding:10px 16px;
  border:1px solid var(--line);
  background:#1a1a1a;
  border-radius:10px;
  color:var(--muted);
  cursor:pointer;
  transition:0.2s;
  font-weight:600
}
.reset:hover{border-color:var(--crimson);color:var(--crimson-light)}

/* Stats in 3 columns */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px}
.stat{
  background:rgba(17,17,17,0.7);
  backdrop-filter:blur(20px);
  border:1px solid var(--line);
  border-radius:16px;
  padding:24px;
  position:relative;
  overflow:hidden;
  transition:0.3s
}
.stat:hover{border-color:var(--crimson);transform:translateY(-3px)}
.stat::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px}
.stat.income::before{background:var(--green)}
.stat.expense::before{background:var(--crimson)}
.stat.balance::before{background:var(--gold)}
.stat small{font-size:11px;letter-spacing:1px;color:var(--muted);font-weight:700;text-transform:uppercase}
.stat h2{font-size:32px;font-weight:800;letter-spacing:-1px;margin-top:12px}
.stat.income h2{color:var(--green)}
.stat.expense h2{color:var(--crimson-light)}
.stat.balance h2{color:var(--gold)}

/* 2 Column Grid for cards */
.grid-2{display:grid;grid-template-columns:1.2fr 0.8fr;gap:20px}

/* Filters */
.filters{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:24px}
.f input,.f select{
  width:100%;
  padding:13px 16px;
  background:#1a1a1a;
  border:1.5px solid var(--line);
  border-radius:12px;
  color:var(--ink);
  font-size:14px;
  outline:none;
  transition:0.2s
}
.f input:focus,.f select:focus{
  border-color:var(--crimson);
  background:#222;
  box-shadow:0 0 0 4px rgba(220,20,60,0.15)
}

/* Card */
.card{
  background:rgba(17,17,17,0.7);
  backdrop-filter:blur(20px);
  border:1px solid var(--line);
  border-radius:16px;
  padding:24px
}
.card h3{font-size:15px;font-weight:700;margin-bottom:18px}

/* Category Bars */
.bar-row{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.bar-label{width:90px;font-size:13px;color:var(--muted)}
.bar-track{flex:1;height:10px;background:#1a1a1a;border-radius:10px;overflow:hidden}
.bar-fill{
  height:100%;
  background:linear-gradient(90deg,var(--crimson),var(--crimson-light));
  border-radius:10px;
  box-shadow:0 0 10px rgba(220,20,60,0.4)
}
.bar-val{width:70px;text-align:right;font-size:13px;font-weight:700}

/* Table */
.table-wrap{overflow:auto}
table{width:100%;border-collapse:collapse}
th{
  font-size:11px;letter-spacing:1px;color:var(--muted);
  text-transform:uppercase;font-weight:700;text-align:left;
  padding:12px;border-bottom:1px solid var(--line)
}
td{padding:16px 12px;font-size:14px;border-bottom:1px solid #1a1a1a}
.amt-income{color:var(--green);font-weight:700}
.amt-expense{color:var(--crimson-light);font-weight:700}
.action{display:flex;gap:16px}
.action a{font-size:13px;text-decoration:none;font-weight:700}
.action .edit{color:var(--crimson-light)}
.action .del{color:#ff6b81}
.empty{padding:40px;text-align:center;color:var(--muted)}

@media(max-width:1100px){
  .container{flex-direction:column}
  .sidebar{width:100%;height:auto;position:relative;border-right:0;border-bottom:2px solid var(--crimson)}
  .sidenav{flex-direction:row;overflow-x:auto}
  .grid-2{grid-template-columns:1fr}
  .stats{grid-template-columns:1fr}
  .main{padding:24px 20px}
</style>
</head><body>
<div class="container">
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="brand"><div class="logo-icon">S</div> Expense Tracker</div>
    <nav class="sidenav">
      <a class="active" href="dashboard.php">📊 Dashboard</a>
      <a href="add_transaction.php">➕ Add transaction</a>
      <a href="monthly_report.php">📅 Monthly report</a>
      <a href="logout.php">🚪 Log out</a>
    </nav>
    <div class="user-info">
      <small>Logged in as</small><br>
      <b><?=h($uname)?></b><br>
      <small>ID: <?=h($uid)?></small>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main">
    <div class="header">
      <div>
        <h1>Dashboard</h1>
        <p><?=count($transactions)?> Transactions this month</p>
      </div>
      <button class="reset" onclick="if(confirm('Reset demo data?')) location.href='reset.php'">Reset demo data</button>
    </div>

    <form class="filters" method="GET">
      <div class="f"><input type="month" name="month" value="<?=h($filterMonth)?>" onchange="this.form.submit()"></div>
      <div class="f">
        <select name="category" onchange="this.form.submit()">
          <option value="All" <?= $filterCat==='All'?'selected':''?>>All categories</option>
          <option value="Food" <?= $filterCat==='Food'?'selected':''?>>Food</option>
          <option value="Education" <?= $filterCat==='Education'?'selected':''?>>Education</option>
          <option value="Travel" <?= $filterCat==='Travel'?'selected':''?>>Travel</option>
          <option value="Other" <?= $filterCat==='Other'?'selected':''?>>Other</option>
          <option value="Salary" <?= $filterCat==='Salary'?'selected':''?>>Salary</option>
        </select>
      </div>
      <div class="f"><input type="text" name="search" placeholder="Search..." value="<?=h($search)?>"></div>
    </form>

    <div class="stats">
      <div class="stat income"><small>Total Income</small><h2>₹<?=number_format($totalIncome)?></h2></div>
      <div class="stat expense"><small>Total Expenses</small><h2>₹<?=number_format($totalExpense)?></h2></div>
      <div class="stat balance"><small>Available Balance</small><h2>₹<?=number_format($balance)?></h2></div>
    </div>

    <div class="grid-2">
      <div class="card">
        <h3>Spending by category</h3>
        <?php if(!$byCat):?><div class="empty">No expenses yet</div>
        <?php else: foreach($byCat as $cat=>$amt): $pct=($maxCat?($amt/$maxCat*100):0); ?>
          <div class="bar-row">
            <div class="bar-label"><?=h($cat)?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?=$pct?>%"></div></div>
            <div class="bar-val">₹<?=number_format($amt)?></div>
          </div>
        <?php endforeach; endif;?>
      </div>

      <div class="card">
        <h3>Quick Stats</h3>
        <div style="display:flex;flex-direction:column;gap:14px">
          <div><small style="color:var(--muted)">Total Transactions</small><h2 style="font-size:24px;margin-top:4px"><?=count($transactions)?></h2></div>
          <div><small style="color:var(--muted)">Avg Expense</small><h2 style="font-size:24px;margin-top:4px">₹<?= $totalExpense>0?number_format($totalExpense/count($transactions)):'0'?></h2></div>
          <div><small style="color:var(--muted)">Top Category</small><h2 style="font-size:24px;margin-top:4px"><?= $byCat?array_key_first($byCat):'-'?></h2></div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:20px">
      <h3>Recent Transactions</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Action</th></tr></thead>
          <tbody>
          <?php if(!$transactions):?><tr><td colspan="5" class="empty">No transactions found</td></tr>
          <?php else: foreach(array_slice($transactions,0,15) as $t):
            $d = $t['date']??$t['transaction_date']??$t['created_at']??'';
            $isInc = ($t['type']??'expense')==='income';
          ?>
            <tr>
              <td><?=h(substr($d,0,10))?></td>
              <td><?=h($t['category']??'Other')?></td>
              <td><?=h($t['description']??$t['note']??'-')?></td>
              <td class="<?=$isInc?'amt-income':'amt-expense'?>"><?=$isInc?'+':'-'?> ₹<?=number_format(floatval($t['amount']??0))?></td>
              <td><div class="action"><a class="edit" href="edit_transaction.php?id=<?=$t['id']?>">Edit</a><a class="del" href="delete_transaction.php?id=<?=$t['id']?>" onclick="return confirm('Delete?')">Delete</a></div></td>
            </tr>
          <?php endforeach; endif;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body></html>