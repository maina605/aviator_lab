<?php
require_once __DIR__ . '/config/db.php';
$pdo = Database::getConnection();

// Session check
$isGuest = !isset($_SESSION['user_id']);
$userBalance = 1500.00;
$username = 'Guest Pilot';
$userRole = 'guest';

if (!$isGuest) {
    $stmt = $pdo->prepare("SELECT username, balance, role FROM aviator_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        $username = $u['username'];
        $userBalance = (float)$u['balance'];
        $userRole = $u['role'] ?? 'player';
    }
} else if (isset($_SESSION['guest_db_id'])) {
    $stmt = $pdo->prepare("SELECT username, balance, role FROM aviator_users WHERE id = ?");
    $stmt->execute([$_SESSION['guest_db_id']]);
    $u = $stmt->fetch();
    if ($u) {
        $username = $u['username'];
        $userBalance = (float)$u['balance'];
        $userRole = $u['role'] ?? 'guest';
    }
}

$isAdmin = ($userRole === 'admin') || (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);
$isGateUnlocked = !empty($_SESSION['admin_gate_unlocked']) && $_SESSION['admin_gate_unlocked'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Aviator PRO - Next Gen Crash Betting Game & AI Predictor</title>
  <meta name="description" content="Play the world's favorite social crash game. Watch the plane fly, multiplier soar, and cash out before it flies away!">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Roboto+Mono:wght@500;700&display=swap" rel="stylesheet">
  
  <!-- Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- 1. HEADER NAVBAR -->
  <header class="header-navbar">
    <div class="brand-section">
      <a href="index.php" class="brand-logo">
        <div class="logo-icon">✈</div>
        <div class="brand-title">Aviator<span>PRO</span></div>
      </a>
      <div id="btnFairBadge" class="badge-fair" title="Click to verify cryptographic fairness">
        🛡️ Provably Fair
      </div>
      <button id="btnOpenPredictor" class="btn-predictor-nav" style="display: none !important;" title="VIP Aviator Predictor AI">
        <span class="radar-icon">🔮</span>
        <span>PREDICTOR AI</span>
      </button>
    </div>

    <div class="header-right">
      <button id="btnRules" class="btn-icon" title="How to play">❓</button>
      <button id="btnToggleSound" class="btn-icon" title="Toggle Sound">🔊</button>
      
      <!-- Wallet Badge -->
      <div class="wallet-badge">
        <div>
          <div class="wallet-label">Balance</div>
          <div id="userWalletBalance" class="wallet-amount"><?= number_format($userBalance, 2) ?> KES</div>
        </div>
        <button id="btnOpenDeposit" class="btn-deposit-top">+ DEPOSIT</button>
      </div>

      <button id="btnOpenWithdraw" class="btn-icon" title="Withdraw Funds" style="font-size: 13px; width:auto; padding:0 10px; font-weight:700;">Payout</button>

      <!-- User Profile -->
      <div id="userProfileMenu" class="user-profile-menu">
        <div id="userAvatarLetter" class="avatar-circle"><?= strtoupper(substr($username, 0, 1)) ?></div>
        <span id="userDisplayName"><?= htmlspecialchars($username) ?></span>
        <button id="btnAuthAction" style="background:none; border:none; color:var(--text-muted); font-size:11px; cursor:pointer;">
          <?= $isGuest ? 'Log In' : 'Log Out' ?>
        </button>
      </div>
    </div>
  </header>

  <!-- 2. RECENT MULTIPLIERS RIBBON -->
  <div id="historyRibbon" class="history-ribbon">
    <span class="history-title">Recent Multipliers:</span>
    <!-- Dynamic pills rendered via app.js -->
  </div>

  <!-- 3. MAIN GAME INTERFACE -->
  <main class="main-wrapper">
    <!-- LEFT SIDEBAR: LIVE BETS & STATS -->
    <aside class="sidebar-bets">
      <div class="sidebar-tabs">
        <button class="tab-btn active" data-tab="all">All Bets</button>
        <button class="tab-btn" data-tab="my">My Bets</button>
        <button class="tab-btn" data-tab="top">Top Wins</button>
      </div>

      <div class="bets-stats-header">
        <span id="totalBetsCount">24 Bets</span>
        <span id="totalBetsSum">18,500 KES</span>
      </div>

      <!-- Live community bets list -->
      <div id="betsList" class="bets-list"></div>

      <!-- User bets list -->
      <div id="myBetsList" class="bets-list" style="display: none;"></div>

      <!-- Top wins leaderboard -->
      <div id="topWinsList" class="bets-list" style="display: none;"></div>
    </aside>

    <!-- RIGHT/CENTER STAGE: RADAR & CONTROLS -->
    <section class="game-stage">
      <!-- Flight Canvas Screen -->
      <div class="canvas-container">
        <canvas id="flightCanvas"></canvas>

        <!-- Live Multiplier Display -->
        <div class="multiplier-overlay">
          <div id="liveMultiplier" class="live-multiplier">1.00x</div>
          <div id="stageStatusMsg" class="stage-status-msg" style="display: none;">FLEW AWAY!</div>
        </div>

        <!-- Waiting / Countdown Overlay -->
        <div id="countdownOverlay" class="countdown-overlay">
          <div class="countdown-title">Waiting for next round</div>
          <div class="countdown-bar-wrap">
            <div id="countdownBar" class="countdown-bar-fill"></div>
          </div>
          <div id="countdownSeconds" class="countdown-seconds">5.0s</div>
        </div>

        <!-- Live In-Game Aviator Predictor HUD (Active for Admin or Gate Unlocked) -->
        <div id="predictorHud" class="predictor-hud-widget" style="<?= ($isAdmin || $isGateUnlocked) ? 'display:flex;' : 'display:none;' ?>">
          <div class="hud-signal-box">
            <span class="hud-tag">
              <span>🔮 PREDICTOR SIGNAL</span>
            </span>
            <span id="hudTargetMultiplier" class="hud-target-val">--.--x</span>
          </div>
          <div class="hud-details">
            <div>Confidence: <strong id="hudConfidence">99.8%</strong></div>
            <div>Safe Exit: <strong id="hudSafeExit">--.--x</strong></div>
            <button type="button" id="btnAutoApplyTarget" class="btn-use-target" title="Apply safe cashout to panel auto cashout">⚡ Use Safe Target</button>
          </div>
          <div style="display:flex; flex-direction:column; gap:4px;">
            <a href="admin.php" class="hud-btn-link" title="Open Command Center">⚡ Command</a>
            <button id="btnToggleHudMin" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; font-weight:bold; padding:0 4px;" title="Minimize HUD">_</button>
          </div>
        </div>
      </div>

      <!-- DUAL BETTING PANELS -->
      <div class="bet-controls-bar">
        <!-- Panel 1 -->
        <div class="panel-card" id="betPanel1">
          <div class="panel-header-tabs">
            <div class="subtabs-pill">
              <button class="subtab-btn active">Bet</button>
            </div>
            <div class="auto-switch-group">
              <span>Auto Bet</span>
              <label class="switch">
                <input type="checkbox" id="toggleAutoBet1">
                <span class="slider"></span>
              </label>
            </div>
          </div>

          <div class="panel-inputs-row">
            <div class="amount-input-box">
              <div class="stepper-input">
                <button type="button" class="btn-step" id="btnMinus1">-</button>
                <input type="number" id="betAmount1" class="input-bet-amount" value="50" min="10" step="10">
                <button type="button" class="btn-step" id="btnPlus1">+</button>
              </div>
              <div class="quick-chips-row">
                <button type="button" class="chip-btn chip-p1" data-amount="20">+20</button>
                <button type="button" class="chip-btn chip-p1" data-amount="50">+50</button>
                <button type="button" class="chip-btn chip-p1" data-amount="100">+100</button>
                <button type="button" class="chip-btn chip-p1" data-amount="500">+500</button>
              </div>
            </div>

            <!-- Big Action Button -->
            <button type="button" id="btnAction1" class="btn-main-action btn-state-bet">
              <span id="btnText1">BET</span>
              <span id="btnSub1" class="btn-cashout-sub">50 KES</span>
            </button>
          </div>

          <!-- Auto Cashout Row -->
          <div class="auto-cashout-row">
            <div class="auto-cashout-label">Auto Cash Out</div>
            <div style="display:flex; align-items:center; gap:8px;">
              <input type="number" id="autoMultiplier1" class="auto-multiplier-input" value="2.00" step="0.1" min="1.05">
              <span>x</span>
              <label class="switch">
                <input type="checkbox" id="toggleAutoCashout1">
                <span class="slider"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Panel 2 -->
        <div class="panel-card" id="betPanel2">
          <div class="panel-header-tabs">
            <div class="subtabs-pill">
              <button class="subtab-btn active">Bet</button>
            </div>
            <div class="auto-switch-group">
              <span>Auto Bet</span>
              <label class="switch">
                <input type="checkbox" id="toggleAutoBet2">
                <span class="slider"></span>
              </label>
            </div>
          </div>

          <div class="panel-inputs-row">
            <div class="amount-input-box">
              <div class="stepper-input">
                <button type="button" class="btn-step" id="btnMinus2">-</button>
                <input type="number" id="betAmount2" class="input-bet-amount" value="100" min="10" step="10">
                <button type="button" class="btn-step" id="btnPlus2">+</button>
              </div>
              <div class="quick-chips-row">
                <button type="button" class="chip-btn chip-p2" data-amount="100">+100</button>
                <button type="button" class="chip-btn chip-p2" data-amount="200">+200</button>
                <button type="button" class="chip-btn chip-p2" data-amount="500">+500</button>
                <button type="button" class="chip-btn chip-p2" data-amount="1000">+1k</button>
              </div>
            </div>

            <!-- Big Action Button -->
            <button type="button" id="btnAction2" class="btn-main-action btn-state-bet">
              <span id="btnText2">BET</span>
              <span id="btnSub2" class="btn-cashout-sub">100 KES</span>
            </button>
          </div>

          <!-- Auto Cashout Row -->
          <div class="auto-cashout-row">
            <div class="auto-cashout-label">Auto Cash Out</div>
            <div style="display:flex; align-items:center; gap:8px;">
              <input type="number" id="autoMultiplier2" class="auto-multiplier-input" value="1.50" step="0.1" min="1.05">
              <span>x</span>
              <label class="switch">
                <input type="checkbox" id="toggleAutoCashout2">
                <span class="slider"></span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- 4. MODALS -->

  <!-- Deposit Modal -->
  <div id="modalDeposit" class="modal-backdrop">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title">💳 Deposit Funds</div>
        <button class="btn-close-modal">✕</button>
      </div>
      <form id="formDeposit" class="modal-body">
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select id="depositMethod" class="form-control">
            <option value="mpesa">Lipa Na M-Pesa (Instant STK)</option>
            <option value="card">Debit / Credit Card</option>
            <option value="voucher">Bonus Voucher Code</option>
          </select>
        </div>

        <div id="phoneFieldGroup" class="form-group">
          <label class="form-label">M-Pesa Mobile Number</label>
          <input type="tel" id="depositPhone" class="form-control" placeholder="e.g. 0712 345 678" value="0712345678">
        </div>

        <div class="form-group">
          <label class="form-label">Deposit Amount (KES)</label>
          <input type="number" id="depositAmount" class="form-control" value="500" min="100" step="50">
          <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Minimum deposit: <strong>100 KES</strong></div>
        </div>

        <div id="voucherFieldGroup" class="form-group" style="display: none;">
          <label class="form-label">Voucher Promo Code</label>
          <input type="text" id="depositVoucher" class="form-control" placeholder="Enter code (e.g. AVIATOR2026)">
          <div style="font-size: 11px; color: var(--color-blue); margin-top: 4px;">Tip: Use code <strong>AVIATOR2026</strong> for 500 KES free bonus!</div>
        </div>

        <button type="submit" class="btn-primary-action">Complete Deposit</button>
      </form>
    </div>
  </div>

  <!-- Withdraw Modal -->
  <div id="modalWithdraw" class="modal-backdrop">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title">💸 Instant Payout</div>
        <button class="btn-close-modal">✕</button>
      </div>
      <form id="formWithdraw" class="modal-body">
        <div class="form-group">
          <label class="form-label">Payout Destination</label>
          <input type="text" id="withdrawAccount" class="form-control" placeholder="M-Pesa Phone or Bank IBAN" value="0712345678">
        </div>

        <div class="form-group">
          <label class="form-label">Amount (KES)</label>
          <input type="number" id="withdrawAmount" class="form-control" value="200" min="50" step="50">
        </div>

        <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, var(--accent-green), #00a843);">Confirm Withdrawal</button>
      </form>
    </div>
  </div>

  <!-- Provably Fair Verifier Modal -->
  <div id="modalFair" class="modal-backdrop">
    <div class="modal-card" style="max-width: 540px;">
      <div class="modal-header">
        <div class="modal-title">🛡️ Provably Fair Verification</div>
        <button class="btn-close-modal">✕</button>
      </div>
      <div class="modal-body">
        <p style="font-size:12px; color:var(--text-muted); line-height:1.5;">
          Aviator rounds are calculated using cryptographic HMAC-SHA256 seeds. The outcome cannot be manipulated by the casino operator or player.
        </p>

        <div class="form-group">
          <label class="form-label">Round ID</label>
          <div id="fairRoundUuid" style="font-family: monospace; font-size: 13px; color: var(--color-blue);">rnd_xxxx</div>
        </div>

        <div class="form-group">
          <label class="form-label">Official Crash Multiplier</label>
          <div id="fairMultiplier" style="font-size: 20px; font-weight: 900; color: var(--accent-green); font-family: monospace;">-</div>
        </div>

        <div class="form-group">
          <label class="form-label">Server Seed (Hex)</label>
          <input type="text" id="fairServerSeed" class="form-control" style="font-family: monospace; font-size: 12px;">
        </div>

        <div class="form-group">
          <label class="form-label">Client Seed</label>
          <input type="text" id="fairClientSeed" class="form-control" style="font-family: monospace; font-size: 12px;">
        </div>

        <div class="form-group">
          <label class="form-label">Combined Hash</label>
          <div id="fairCombinedHash" style="font-family: monospace; font-size: 11px; word-break: break-all; color: var(--text-muted);">-</div>
        </div>

        <button type="button" id="btnRunVerify" class="btn-primary-action" style="background: var(--bg-hover); border:1px solid var(--border-color);">
          Verify Seed Calculation
        </button>

        <div id="fairVerifyResult"></div>
      </div>
    </div>
  </div>

  <!-- How to Play Modal -->
  <div id="modalRules" class="modal-backdrop">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title">📖 How To Play Aviator</div>
        <button class="btn-close-modal">✕</button>
      </div>
      <div class="modal-body" style="font-size: 13px; line-height: 1.6; color: var(--text-muted);">
        <div style="display:flex; align-items:flex-start; gap:12px;">
          <span style="font-size:24px;">1️⃣</span>
          <div>
            <strong style="color:#fff;">Place Your Bets:</strong> Select your desired bet amount on Panel 1, Panel 2, or both before the airplane departs.
          </div>
        </div>
        <div style="display:flex; align-items:flex-start; gap:12px;">
          <span style="font-size:24px;">2️⃣</span>
          <div>
            <strong style="color:#fff;">Watch The Climb:</strong> The plane takes off and the multiplier starts increasing from 1.00x upwards.
          </div>
        </div>
        <div style="display:flex; align-items:flex-start; gap:12px;">
          <span style="font-size:24px;">3️⃣</span>
          <div>
            <strong style="color:#fff;">Cash Out in Time:</strong> Hit the "CASH OUT" button before the plane flies away to claim your winnings (Bet × Multiplier). If you don't cash out in time, the bet is lost!
          </div>
        </div>
        <div style="display:flex; align-items:flex-start; gap:12px;">
          <span style="font-size:24px;">4️⃣</span>
          <div>
            <strong style="color:#fff;">Auto Cash Out:</strong> Set a target multiplier (e.g. 2.00x) and the system will automatically cash out for you when reached.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Auth Modal (Login / Sign Up) -->
  <div id="modalAuth" class="modal-backdrop">
    <div class="modal-card">
      <div class="modal-header">
        <div class="sidebar-tabs" style="width:100%; margin:-16px -20px; border:none;">
          <button id="tabAuthLogin" class="tab-btn active">Sign In</button>
          <button id="tabAuthRegister" class="tab-btn">Create Account</button>
        </div>
        <button class="btn-close-modal" style="position: absolute; right: 16px; top: 14px;">✕</button>
      </div>

      <!-- Login Form -->
      <form id="formLogin" class="modal-body">
        <div class="form-group">
          <label class="form-label">Username or Email</label>
          <input type="text" id="loginUsername" class="form-control" required placeholder="Enter username">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" id="loginPassword" class="form-control" required placeholder="Enter password">
        </div>
        <button type="submit" class="btn-primary-action">Sign In</button>
      </form>

      <!-- Register Form -->
      <form id="formRegister" class="modal-body" style="display: none;">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" id="regUsername" class="form-control" required placeholder="Choose a pilot handle">
        </div>
        <div class="form-group">
          <label class="form-label">Email (Optional)</label>
          <input type="email" id="regEmail" class="form-control" placeholder="your@email.com">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" id="regPassword" class="form-control" required placeholder="At least 6 characters">
        </div>
        <button type="submit" class="btn-primary-action">Sign Up (+2,000 KES Bonus)</button>
      </form>
    </div>
  </div>

  <!-- Master Passkey & Aviator Predictor Modal -->
  <div id="modalAdminGate" class="modal-backdrop">
    <div class="modal-card" style="max-width: 560px;">
      <div class="modal-header">
        <div class="modal-title">🔮 Aviator Predictor AI Hub</div>
        <button class="btn-close-modal">✕</button>
      </div>
      
      <!-- Stage A: Enter Master Passkey to unlock -->
      <div id="gateStepUnlock" class="modal-body">
        <div class="gate-shield-header">
          <div class="shield-icon">🔮</div>
          <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(0,229,255,0.12); border:1px solid rgba(0,229,255,0.3); padding:4px 10px; border-radius:14px; margin-bottom:10px;">
            <span style="color:#00e5ff; font-weight:800; font-size:11px;">NEURAL PREDICTOR ENGINE: ONLINE (18ms)</span>
          </div>
          <h3 style="font-size: 18px; margin: 0 0 6px 0;">Aviator AI Neural Security Gate</h3>
          <p style="font-size: 12px; color: var(--text-muted); margin: 0;">
            Enter the master passkey to unlock live flight forecasts, telemetry, and administrative overrides.
          </p>
        </div>
        <form id="formGateModal">
          <div class="form-group">
            <label class="form-label">Master Passkey</label>
            <div style="position: relative;">
              <input type="password" id="gateModalPassword" class="form-control" placeholder="Enter passkey to unlock..." required style="padding-right: 42px;">
              <button type="button" class="btn-eye-toggle" onclick="togglePwdVisibility('gateModalPassword', this)" title="Toggle Password Privacy" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #8b92a5; cursor: pointer; font-size: 16px; padding: 4px;">👁️</button>
            </div>
          </div>
          <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, #00b0ff, #0081cb); box-shadow: 0 4px 15px rgba(0, 176, 255, 0.4);">
            🔓 Verify & Unlock AI Engine
          </button>
        </form>
      </div>

      <!-- Stage B: Gate Unlocked - Choice to Register or Login -->
      <div id="gateStepAuth" class="modal-body" style="display: none;">
        <div class="gate-shield-header">
          <div class="gate-verified-chip">
            <span>✓</span> <span>Master Passkey Verified</span>
          </div>
          <div style="display: flex; background: var(--bg-main); border-radius: 8px; padding: 4px; margin-bottom: 16px;">
            <button id="tabGateLogin" class="subtab-btn active" style="flex:1;">Sign In</button>
            <button id="tabGateRegister" class="subtab-btn" style="flex:1;">Register Admin</button>
          </div>
        </div>

        <!-- Admin Sign In in Modal -->
        <form id="formGateLogin">
          <div class="form-group">
            <label class="form-label">Admin Username or Email</label>
            <input type="text" id="gateLoginUser" class="form-control" placeholder="Admin username" required>
          </div>
          <div class="form-group">
            <label class="form-label">Admin Password</label>
            <div style="position: relative;">
              <input type="password" id="gateLoginPass" class="form-control" placeholder="Admin password" required style="padding-right: 42px;">
              <button type="button" class="btn-eye-toggle" onclick="togglePwdVisibility('gateLoginPass', this)" title="Toggle Password Privacy" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #8b92a5; cursor: pointer; font-size: 16px; padding: 4px;">👁️</button>
            </div>
          </div>
          <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, #00e5ff, #0091ea); color: #000; font-weight: 900;">
            ⚡ Sign In to Predictor
          </button>
        </form>

        <!-- Admin Register in Modal -->
        <form id="formGateRegister" style="display: none;">
          <div class="form-group">
            <label class="form-label">Admin Pilot Username</label>
            <input type="text" id="gateRegUser" class="form-control" placeholder="e.g. MasterAdmin" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email (Optional)</label>
            <input type="email" id="gateRegEmail" class="form-control" placeholder="admin@aviator.local">
          </div>
          <div class="form-group">
            <label class="form-label">Personal Admin Password</label>
            <div style="position: relative;">
              <input type="password" id="gateRegPass" class="form-control" placeholder="At least 6 characters" required style="padding-right: 42px;">
              <button type="button" class="btn-eye-toggle" onclick="togglePwdVisibility('gateRegPass', this)" title="Toggle Password Privacy" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #8b92a5; cursor: pointer; font-size: 16px; padding: 4px;">👁️</button>
            </div>
          </div>
          <input type="hidden" id="gateRegMasterKey" value="">
          <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, #e51b24, #b71c1c);">
            👑 Register Administrator (+50,000 KES)
          </button>
        </form>
      </div>

      <!-- Stage C: Admin Already Logged In / Predictor Active -->
      <div id="gateStepActive" class="modal-body" style="display: none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; background:rgba(0,229,255,0.08); padding:8px 12px; border-radius:8px; border:1px solid rgba(0,229,255,0.2);">
          <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-weight:800; font-size:12px; color:#00e5ff;">AVIATOR NEURAL RADAR ACTIVE</span>
          </div>
          <span style="font-size:11px; color:#00e676; font-family:monospace;">LATENCY: 18ms</span>
        </div>

        <div style="background:rgba(0,0,0,0.3); border:1px solid rgba(0,229,255,0.25); border-radius:12px; padding:16px; margin-bottom:16px; text-align:center;">
          <div style="font-size:11px; color:#8b92a5; text-transform:uppercase; font-weight:700;">Live Forecast • Next Round Multiplier</div>
          <div id="modalTargetMultiplier" style="font-size:42px; font-weight:900; color:#00e5ff; font-family:'Roboto Mono',monospace; text-shadow:0 0 15px rgba(0,229,255,0.5); margin:6px 0;">--.--x</div>
          <div style="display:flex; justify-content:center; gap:16px; font-size:12px;">
            <div>Safe Exit: <strong id="modalSafeExit" style="color:#ffd600;">--.--x</strong></div>
            <div>Confidence: <strong id="modalConfidence" style="color:#00e676;">99.8%</strong></div>
            <div>Risk: <strong id="modalRiskLevel" style="color:#ff5252;">CALCULATING</strong></div>
          </div>
        </div>

        <div style="display: flex; gap: 10px; margin-bottom: 12px;">
          <button id="btnModalApplyTarget" class="btn-primary-action" style="flex:1; background:linear-gradient(135deg, #00c853, #009624);">
            🎯 Auto-Apply Safe Target to Bet Panels
          </button>
        </div>

        <div style="display: flex; gap: 10px;">
          <a href="admin.php" class="btn-primary-action" style="flex:1; text-decoration: none; text-align: center; background: linear-gradient(135deg, #00e5ff, #0091ea); color: #000; font-weight: 900;">
            ⚡ Open Command Center
          </a>
          <button id="btnToggleHudVisibility" class="btn-primary-action" style="flex:1; background: var(--bg-hover);">
            Toggle HUD Overlay
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- Toast Container -->
  <div id="toastContainer" class="toast-container"></div>

  <!-- Initial State Injection -->
  <script>
    window.INITIAL_AUTH = {
      is_admin: <?= json_encode($isAdmin) ?>,
      gate_unlocked: <?= json_encode($isGateUnlocked) ?>,
      role: <?= json_encode($userRole) ?>
    };
  </script>

  <!-- Core Scripts -->
  <script src="assets/js/audio.js"></script>
  <script src="assets/js/game.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
