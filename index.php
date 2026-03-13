<?php
error_reporting(0);
session_start();

// DATABASE CONNECTION
$conn = new mysqli("localhost", "root", "", "dukalangu_db");
if ($conn->connect_error) { die("Connection failed."); }

// 1. LUGHA LOGIC
if (isset($_GET['lang'])) { $_SESSION['lang'] = $_GET['lang']; }
$lang = $_SESSION['lang'] ?? 'sw';
$ui = ($lang == 'en') ? 
    ['title'=>'DukaLangu Pro', 'bal'=>'Equity Capital', 'profit'=>'Period Profit', 'sale'=>'Record Revenue', 'exp'=>'Stock Inflow', 'desc'=>'Item Name', 'qty'=>'Qty', 'amt'=>'Price @ Unit', 'save'=>'Commit', 'recent'=>'Transactions', 'view_all'=>'View All', 'back'=>'Dashboard', 'login'=>'Sign In', 'signup'=>'Register Shop', 'user'=>'Username', 'pass'=>'Password', 'shop'=>'Shop Name', 'have_acc'=>'Have an account? Login', 'no_acc'=>'New Shop? Register', 'all_rec'=>'Filtered Records', 'from'=>'From', 'to'=>'To', 'filter'=>'Filter', 'f_day'=>'DAY', 'f_month'=>'MONTH', 'f_year'=>'YEAR', 'f_range'=>'RANGE'] : 
    ['title'=>'DukaLangu Pro', 'bal'=>'Mtaji wa Sasa', 'profit'=>'Faida ya Kipindi', 'sale'=>'Rekodi Mauzo', 'exp'=>'Ingiza Mzigo', 'desc'=>'Jina la Bidhaa', 'qty'=>'Idadi', 'amt'=>'Bei @ Moja', 'save'=>'Hifadhi', 'recent'=>'Miamala', 'view_all'=>'Ona Zote', 'back'=>'Dashibodi', 'login'=>'Ingia', 'signup'=>'Sajili Duka', 'user'=>'Jina la Mtumiaji', 'pass'=>'Neno la Siri', 'shop'=>'Jina la Duka', 'have_acc'=>'Tayari una akaunti? Ingia', 'no_acc'=>'Duka Jipya? Sajili hapa', 'all_rec'=>'Kumbukumbu Zilizochanjuliwa', 'from'=>'Kuanzia', 'to'=>'Hadi', 'filter'=>'Chuja', 'f_day'=>'SIKU', 'f_month'=>'MWEZI', 'f_year'=>'MWAKA', 'f_range'=>'MUDA'];

// 2. AUTH LOGIC
if (isset($_POST['do_signup'])) {
    $shop = $conn->real_escape_string($_POST['shop_name']); 
    $user = $conn->real_escape_string($_POST['username']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (shop_name, username, password) VALUES ('$shop', '$user', '$pass')");
    $_SESSION['user_id'] = $conn->insert_id; $_SESSION['shop_name'] = $shop;
    header("Location: index.php"); exit();
}

if (isset($_POST['do_login'])) {
    $user = $conn->real_escape_string($_POST['username']); $pass = $_POST['password'];
    $res = $conn->query("SELECT * FROM users WHERE username='$user'");
    if ($res && $u = $res->fetch_assoc()) {
        if (password_verify($pass, $u['password'])) {
            $_SESSION['user_id'] = $u['id']; $_SESSION['shop_name'] = $u['shop_name'];
            header("Location: index.php"); exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') { session_destroy(); header("Location: index.php"); exit(); }

// 3. DASHBOARD LOGIC
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    if (isset($_POST['save_trans'])) {
        $t = $_POST['type']; $d = $conn->real_escape_string($_POST['desc']); 
        $q = (int)$_POST['qty']; $p = (float)$_POST['amt'];
        $total = $q * $p;
        $conn->query("INSERT INTO transactions (user_id, type, description, quantity, amount) VALUES ('$uid', '$t', '$d', $q, $total)");
        header("Location: index.php"); exit();
    }

    if (isset($_GET['del'])) {
        $id = (int)$_GET['del'];
        $conn->query("DELETE FROM transactions WHERE id=$id AND user_id=$uid");
        header("Location: index.php"); exit();
    }

    $f_from = $_GET['f_from'] ?? date('Y-m-d');
    $f_to = $_GET['f_to'] ?? date('Y-m-d');
    $f_type = $_GET['f_type'] ?? 'day';

    $where_clause = "user_id=$uid";
    if ($f_type == 'day') { $where_clause .= " AND DATE(created_at) = '$f_from'"; }
    elseif ($f_type == 'month') { $where_clause .= " AND MONTH(created_at) = MONTH('$f_from') AND YEAR(created_at) = YEAR('$f_from')"; }
    elseif ($f_type == 'year') { $where_clause .= " AND YEAR(created_at) = YEAR('$f_from')"; }
    elseif ($f_type == 'range') { $where_clause .= " AND DATE(created_at) BETWEEN '$f_from' AND '$f_to'"; }

    // FAIDA YA KIPINDI HUSIKA
    $profit_res = $conn->query("SELECT SUM(CASE WHEN type='sale' THEN amount ELSE -amount END) as p FROM transactions WHERE $where_clause");
    $period_profit = $profit_res->fetch_assoc()['p'] ?? 0;

    // MTAJI WA JUMLA (TOTAL BALANCE)
    $bal_res = $conn->query("SELECT SUM(CASE WHEN type='sale' THEN amount ELSE -amount END) as b FROM transactions WHERE user_id=$uid");
    $balance = $bal_res->fetch_assoc()['b'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $ui['title']; ?></title>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0046ad">
    <style>
        :root { --blue: #0046ad; --dark: #001d4a; --green: #00c853; --red: #ff3d00; --bg: #f4f7fc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; font-size: 16px; color: #333; overflow-x: hidden; }

        /* AUTH SECTIONS */
        .auth-container { min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; background: linear-gradient(45deg, var(--dark), var(--blue)); box-sizing: border-box; }
        .login-card { background: white; padding: 35px 25px; border-radius: 30px; width: 100%; max-width: 380px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .auth-lang-switcher { display: flex; justify-content: center; gap: 8px; margin-bottom: 20px; }
        .lang-pill { padding: 6px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; text-decoration: none; color: #888; background: #f0f2f5; transition: 0.3s; }
        .lang-pill.active { background: var(--blue); color: white; }
        .auth-input { width: 100%; padding: 14px; margin: 10px 0; border-radius: 12px; border: 1.5px solid #eee; font-size: 16px; outline: none; box-sizing: border-box; }
        .btn-auth { width: 100%; padding: 16px; border-radius: 12px; border: none; background: var(--blue); color: white; font-weight: 800; cursor: pointer; font-size: 16px; }

        /* DASHBOARD TOP */
        .top-fixed-section { position: sticky; top: 0; z-index: 100; background: var(--bg); border-bottom: 1.5px solid rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: white; }
        .balance-card { background: linear-gradient(135deg, var(--blue), var(--dark)); color: white; border-radius: 20px; padding: 25px; margin: 15px; text-align: center; box-shadow: 0 8px 20px rgba(0,70,173,0.15); position: relative; z-index: 2; }
        .balance-card h1 { margin: 5px 0; font-size: 30px; font-weight: 900; }

        /* NEW PROFIT MINI CARD WITH MOTION */
        .profit-mini-card { background: var(--green); color: white; margin: -25px 30px 15px 30px; padding: 15px 20px; border-radius: 0 0 20px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 20px rgba(0,200,83,0.15); animation: slideDown 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; z-index: 1; }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .profit-mini-card strong { font-size: 18px; font-weight: 900; }
        .profit-mini-card small { font-size: 10px; font-weight: 800; text-transform: uppercase; opacity: 0.9; }

        /* ANIMATED FILTER BAR */
        .filter-container { background: white; margin: 0 15px 15px 15px; padding: 10px; border-radius: 18px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .filter-nav { display: flex; position: relative; background: #f0f2f5; border-radius: 12px; padding: 4px; margin-bottom: 12px; }
        .filter-nav button { flex: 1; border: none; background: none; padding: 10px; font-size: 10px; font-weight: 800; color: #777; cursor: pointer; position: relative; z-index: 2; transition: 0.3s; }
        .filter-nav button.active { color: var(--blue); }
        .nav-slider { position: absolute; height: 34px; top: 4px; left: 4px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55); z-index: 1; }
        .date-inputs-row { display: flex; gap: 10px; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        .date-field { flex: 1; }
        .date-field label { font-size: 10px; font-weight: 800; color: #999; margin-left: 5px; text-transform: uppercase; }
        .date-input { width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #f0f2f5; background: #fafafa; font-size: 13px; font-weight: 700; color: var(--dark); outline: none; box-sizing: border-box; }

        /* ACTIONS */
        .action-btns { display: flex; gap: 10px; padding: 0 15px; margin-bottom: 15px; }
        .btn-act { flex: 1; padding: 14px; border: none; border-radius: 15px; color: white; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; }

        /* LEDGER */
        .ledger { background: white; margin: 5px 15px 50px 15px; border-radius: 20px; padding: 15px; }
        .item { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #f9f9f9; }
        .item-info span { font-size: 16px; font-weight: 700; color: var(--dark); display: block; }
        .item-info small { color: #aaa; font-size: 12px; font-weight: 600; }
        .item-right { text-align: right; display: flex; align-items: center; gap: 12px; }
        .amt { font-weight: 900; font-size: 17px; }
        .btn-del { color: #eee; background: none; border: none; cursor: pointer; transition: 0.2s; padding: 5px; }
        .btn-del:hover { color: var(--red); }

        .sheet { display: none; position: fixed; bottom: 0; left: 0; right: 0; background: white; padding: 25px; border-radius: 25px 25px 0 0; z-index: 1001; transform: translateY(100%); transition: 0.3s; }
        .sheet.open { display: block; transform: translateY(0); }
        .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 999; backdrop-filter: blur(4px); }
        .icon-svg { width: 20px; height: 20px; fill: none; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['user_id'])): 
    $mode = $_GET['auth'] ?? 'login'; ?>
    <div class="auth-container">
        <div class="login-card">
            <div class="auth-lang-switcher">
                <a href="?lang=sw&auth=<?php echo $mode; ?>" class="lang-pill <?php echo $lang=='sw'?'active':''; ?>">KISWAHILI</a>
                <a href="?lang=en&auth=<?php echo $mode; ?>" class="lang-pill <?php echo $lang=='en'?'active':''; ?>">ENGLISH</a>
            </div>
            <h2 style="margin:10px 0; color:var(--dark); font-weight:900;"><?php echo $ui['title']; ?></h2>
            <form method="POST">
                <?php if($mode == 'signup'): ?>
                    <input type="text" name="shop_name" class="auth-input" placeholder="<?php echo $ui['shop']; ?>" required>
                <?php endif; ?>
                <input type="text" name="username" class="auth-input" placeholder="<?php echo $ui['user']; ?>" required>
                <input type="password" name="password" class="auth-input" placeholder="<?php echo $ui['pass']; ?>" required>
                <button type="submit" name="<?php echo ($mode=='login'?'do_login':'do_signup'); ?>" class="btn-auth"><?php echo ($mode=='login'?$ui['login']:$ui['signup']); ?></button>
            </form>
            <div style="margin-top:20px;">
                <a href="?auth=<?php echo ($mode=='login'?'signup':'login'); ?>&lang=<?php echo $lang; ?>" style="color:var(--blue); text-decoration:none; font-size:14px; font-weight:700;"><?php echo ($mode=='login'?$ui['no_acc']:$ui['have_acc']); ?></a>
            </div>
        </div>
    </div>
<?php else: ?>

    <div class="top-fixed-section">
        <div class="header">
            <strong style="color:var(--blue); font-size:18px; font-weight:900;"><?php echo $ui['title']; ?></strong>
            <div style="display:flex; align-items:center; gap:10px;">
                <div class="auth-lang-switcher" style="margin-bottom:0;">
                    <a href="?lang=sw" class="lang-pill <?php echo $lang=='sw'?'active':''; ?>" style="padding:4px 10px; font-size:10px;">SW</a>
                    <a href="?lang=en" class="lang-pill <?php echo $lang=='en'?'active':''; ?>" style="padding:4px 10px; font-size:10px;">EN</a>
                </div>
                <a href="?action=logout" style="color:var(--red); text-decoration:none; font-size:10px; font-weight:900; border: 1.5px solid var(--red); padding: 4px 8px; border-radius: 8px;">OUT</a>
            </div>
        </div>

        <div class="balance-card">
            <small style="font-weight:800; opacity:0.8;"><?php echo $_SESSION['shop_name']; ?></small>
            <h1>TSh <?php echo number_format($balance, 0); ?></h1>
            <small><?php echo $ui['bal']; ?></small>
        </div>

        <div class="profit-mini-card">
            <div>
                <small><?php echo $ui['profit']; ?></small><br>
                <strong>TSh <?php echo number_format($period_profit, 0); ?></strong>
            </div>
            <svg class="icon-svg" viewBox="0 0 24 24" style="stroke:white; stroke-width:3;"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
        </div>

        <div class="action-btns">
            <button class="btn-act" style="background:var(--green);" onclick="openSheet('sale')">
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M12 5V19M5 12H19" stroke="white" stroke-width="3" stroke-linecap="round"/></svg>
                <?php echo $ui['sale']; ?>
            </button>
            <button class="btn-act" style="background:var(--red);" onclick="openSheet('expense')">
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M5 12H19" stroke="white" stroke-width="3" stroke-linecap="round"/></svg>
                <?php echo $ui['exp']; ?>
            </button>
        </div>

        <div class="filter-container">
            <form method="GET" action="index.php" id="filterForm">
                <input type="hidden" name="f_type" id="f_type" value="<?php echo $f_type; ?>">
                <div class="filter-nav">
                    <div class="nav-slider" id="navSlider"></div>
                    <button type="button" class="<?php echo $f_type=='day'?'active':''; ?>" onclick="setFilter('day', 0)"><?php echo $ui['f_day']; ?></button>
                    <button type="button" class="<?php echo $f_type=='month'?'active':''; ?>" onclick="setFilter('month', 1)"><?php echo $ui['f_month']; ?></button>
                    <button type="button" class="<?php echo $f_type=='year'?'active':''; ?>" onclick="setFilter('year', 2)"><?php echo $ui['f_year']; ?></button>
                    <button type="button" class="<?php echo $f_type=='range'?'active':''; ?>" onclick="setFilter('range', 3)"><?php echo $ui['f_range']; ?></button>
                </div>
                <div class="date-inputs-row">
                    <div class="date-field">
                        <label><?php echo $ui['from']; ?></label>
                        <input type="date" name="f_from" class="date-input" value="<?php echo $f_from; ?>" onchange="document.getElementById('filterForm').submit()">
                    </div>
                    <?php if($f_type == 'range'): ?>
                    <div class="date-field">
                        <label><?php echo $ui['to']; ?></label>
                        <input type="date" name="f_to" class="date-input" value="<?php echo $f_to; ?>" onchange="document.getElementById('filterForm').submit()">
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="ledger">
        <div style="font-size:11px; color:#bbb; font-weight:800; margin-bottom:12px; letter-spacing:1px;"><?php echo strtoupper($ui['recent']); ?></div>
        <?php
        $list = $conn->query("SELECT * FROM transactions WHERE $where_clause ORDER BY created_at DESC");
        if ($list && $list->num_rows > 0):
            while ($row = $list->fetch_assoc()):
                $isSale = ($row['type'] == 'sale'); ?>
                <div class="item">
                    <div class="item-info">
                        <span><?php echo $row['description']; ?></span>
                        <small>Qty: <?php echo $row['quantity']; ?> • <?php echo date('d M, H:i', strtotime($row['created_at'])); ?></small>
                    </div>
                    <div class="item-right">
                        <span class="amt" style="color:<?php echo $isSale ? 'var(--green)' : 'var(--red)'; ?>;">
                            <?php echo ($isSale ? '+' : '-') . number_format($row['amount']); ?>
                        </span>
                        <a href="?del=<?php echo $row['id']; ?>&lang=<?php echo $lang; ?>&f_type=<?php echo $f_type; ?>&f_from=<?php echo $f_from; ?>&f_to=<?php echo $f_to; ?>" class="btn-del" onclick="return confirm('Futa?')">
                            <svg class="icon-svg" viewBox="0 0 24 24" style="stroke:currentColor; stroke-width:2.5;"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </a>
                    </div>
                </div>
            <?php endwhile; 
        else: echo "<p style='text-align:center; color:#ccc; font-size:14px; padding:30px;'>Hakuna data.</p>"; endif; ?>
    </div>

    <div id="overlay" class="overlay" onclick="closeSheet()"></div>
    <div id="sheet" class="sheet">
        <h2 id="sheetTitle" style="margin-top:0; font-weight:900;"></h2>
        <form method="POST">
            <input type="hidden" name="type" id="sheetType">
            <input type="text" name="desc" class="auth-input" placeholder="<?php echo $ui['desc']; ?>" required>
            <div style="display:flex; gap:10px;">
                <input type="number" name="qty" class="auth-input" placeholder="<?php echo $ui['qty']; ?>" required>
                <input type="number" name="amt" class="auth-input" placeholder="<?php echo $ui['amt']; ?>" required>
            </div>
            <button type="submit" name="save_trans" id="sheetBtn" class="btn-auth"></button>
            <button type="button" onclick="closeSheet()" style="width:100%; border:none; background:none; color:#aaa; margin-top:15px; font-weight:800; cursor:pointer;">GHAIRI</button>
        </form>
    </div>

    <script>
        function updateSlider(index) {
            const slider = document.getElementById('navSlider');
            const width = 100 / 4;
            slider.style.width = `calc(${width}% - 8px)`;
            slider.style.left = `calc(${index * width}% + 4px)`;
        }

        function setFilter(type, index) {
            document.getElementById('f_type').value = type;
            updateSlider(index);
            setTimeout(() => document.getElementById('filterForm').submit(), 150);
        }

        window.onload = () => {
            const currentType = "<?php echo $f_type; ?>";
            const types = ['day', 'month', 'year', 'range'];
            updateSlider(types.indexOf(currentType));
        };

        function openSheet(type) {
            document.getElementById('sheetTitle').innerText = (type === 'sale') ? '<?php echo $ui['sale']; ?>' : '<?php echo $ui['exp']; ?>';
            document.getElementById('sheetBtn').innerText = '<?php echo $ui['save']; ?>';
            document.getElementById('sheetBtn').style.background = (type === 'sale') ? 'var(--green)' : 'var(--red)';
            document.getElementById('sheetType').value = type;
            document.getElementById('overlay').style.display = 'block';
            const s = document.getElementById('sheet');
            s.style.display = 'block';
            setTimeout(() => s.classList.add('open'), 10);
        }
        function closeSheet() {
            const s = document.getElementById('sheet');
            s.classList.remove('open');
            setTimeout(() => { s.style.display = 'none'; document.getElementById('overlay').style.display = 'none'; }, 300);
        }
    </script>
<?php endif; ?>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('DukaLangu Offline Ready!'))
        .catch(err => console.log('Service Worker Failed', err));
    });
  }
</script>
</body>
</html>