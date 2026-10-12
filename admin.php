<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/predictor_engine.php';

$pdo = Database::getConnection();

// Session state
$isGateUnlocked = !empty($_SESSION['admin_gate_unlocked']) && $_SESSION['admin_gate_unlocked'] === true;
$isAdmin = (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) || 
           (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Check if any admin exists in database
$hasAdmin = false;
try {
    $stmt = $pdo->query("SELECT id FROM aviator_users WHERE role = 'admin' LIMIT 1");
    $hasAdmin = (bool)$stmt->fetch();
} catch (Throwable $e) {}

$adminUsername = 'Administrator';
if ($isAdmin && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username, balance FROM aviator_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch();
        if ($u) {
            $adminUsername = $u['username'];
        }
    } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aviator PRO - Master Predictor & Admin Command Center</title>
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Roboto+Mono:wght@500;700&display=swap" rel="stylesheet">
  
  <!-- Global Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
  
  <style>
    /* Specialized Admin & Predictor Styling */
    :root {
      --neon-cyan: #00e5ff;
      --neon-purple: #b388ff;
      --neon-green: #00e676;
      --neon-red: #ff1744;
      --neon-gold: #ffd600;
      --bg-dark-card: rgba(18, 20, 29, 0.85);
    }
    
    body {
      background: radial-gradient(circle at 50% 10%, #151928 0%, #0a0b10 100%);
      min-height: 100vh;
      color: #f0f2f5;
      font-family: 'Outfit', sans-serif;
    }

    .admin-container {
      max-width: 1240px;
      margin: 0 auto;
      padding: 24px 20px;
    }

    .admin-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 24px;
      background: var(--bg-dark-card);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      backdrop-filter: blur(12px);
      margin-bottom: 24px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    }

    .brand-glow {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }

    .brand-glow .icon {
      font-size: 26px;
      filter: drop-shadow(0 0 10px #e51b24);
    }

    .brand-glow .title {
      font-size: 20px;
      font-weight: 900;
      color: #fff;
      letter-spacing: 0.5px;
    }

    .brand-glow .badge {
      background: linear-gradient(135deg, #e51b24, #ff5252);
      color: #fff;
      font-size: 10px;
      font-weight: 800;
      padding: 3px 8px;
      border-radius: 4px;
      letter-spacing: 1px;
    }

    .admin-status-badge {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 20px;
      background: rgba(0, 230, 118, 0.1);
      border: 1px solid rgba(0, 230, 118, 0.3);
      color: var(--neon-green);
    }

    .pulse-dot {
      width: 8px;
      height: 8px;
      background: var(--neon-green);
      border-radius: 50%;
      box-shadow: 0 0 10px var(--neon-green);
      animation: pulseGlow 1.5s infinite;
    }

    @keyframes pulseGlow {
      0% { transform: scale(0.95); opacity: 0.8; }
      50% { transform: scale(1.3); opacity: 1; }
      100% { transform: scale(0.95); opacity: 0.8; }
    }

    /* Auth & Gate Lock Screen */
    .gate-lock-card {
      max-width: 480px;
      margin: 80px auto;
      background: var(--bg-dark-card);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 36px 32px;
      text-align: center;
      backdrop-filter: blur(16px);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
    }

    .gate-icon {
      font-size: 56px;
      margin-bottom: 16px;
      filter: drop-shadow(0 0 20px rgba(0, 229, 255, 0.4));
    }

    .gate-title {
      font-size: 24px;
      font-weight: 900;
      margin-bottom: 8px;
      background: linear-gradient(135deg, #fff, #90caf9);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .gate-subtitle {
      font-size: 13px;
      color: #8b92a5;
      margin-bottom: 24px;
      line-height: 1.5;
    }

    /* Grid Layout for Active Dashboard */
    .dashboard-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      margin-bottom: 24px;
    }

    @media (max-width: 960px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Predictor Hero Card */
    .predictor-hero {
      background: linear-gradient(135deg, rgba(20, 24, 38, 0.95), rgba(12, 14, 22, 0.95));
      border: 1px solid rgba(0, 229, 255, 0.25);
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 0 35px rgba(0, 229, 255, 0.08);
      position: relative;
      overflow: hidden;
    }

    .predictor-hero::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -20%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(0, 229, 255, 0.12), transparent 70%);
      pointer-events: none;
    }

    .target-multiplier-display {
      font-family: 'Roboto Mono', monospace;
      font-size: 68px;
      font-weight: 900;
      line-height: 1;
      margin: 16px 0;
      color: var(--neon-cyan);
      text-shadow: 0 0 25px rgba(0, 229, 255, 0.6);
      display: flex;
      align-items: baseline;
      gap: 12px;
    }

    .signal-pills-row {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin: 20px 0;
    }

    .signal-pill {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .signal-pill strong {
      color: #fff;
    }

    /* Upcoming Queue Table */
    .queue-card {
      background: var(--bg-dark-card);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 24px;
      margin-top: 24px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .table-modern {
      width: 100%;
      border-collapse: collapse;
      margin-top: 14px;
    }

    .table-modern th {
      text-align: left;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: #8b92a5;
      padding: 12px 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .table-modern td {
      padding: 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      font-size: 14px;
    }

    .table-modern tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    .mult-badge {
      font-family: 'Roboto Mono', monospace;
      font-weight: 700;
      font-size: 15px;
      padding: 4px 10px;
      border-radius: 6px;
      display: inline-block;
    }

    .badge-cyan { background: rgba(0, 229, 255, 0.15); color: var(--neon-cyan); border: 1px solid rgba(0, 229, 255, 0.3); }
    .badge-purple { background: rgba(179, 136, 255, 0.15); color: var(--neon-purple); border: 1px solid rgba(179, 136, 255, 0.3); }
    .badge-gold { background: rgba(255, 214, 0, 0.15); color: var(--neon-gold); border: 1px solid rgba(255, 214, 0, 0.3); }
    .badge-red { background: rgba(255, 23, 68, 0.15); color: var(--neon-red); border: 1px solid rgba(255, 23, 68, 0.3); }

    /* Override Control Card */
    .control-card {
      background: var(--bg-dark-card);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .input-cyber {
      width: 100%;
      background: rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      padding: 14px 16px;
      color: #fff;
      font-size: 16px;
      font-family: 'Roboto Mono', monospace;
      outline: none;
      transition: all 0.2s ease;
      box-sizing: border-box;
      margin: 12px 0;
    }

    .input-cyber:focus {
      border-color: var(--neon-cyan);
      box-shadow: 0 0 12px rgba(0, 229, 255, 0.3);
    }

    .btn-cyber {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 10px;
      font-weight: 800;
      font-size: 14px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-cyber-primary {
      background: linear-gradient(135deg, #00b0ff, #0081cb);
      color: #fff;
      box-shadow: 0 4px 20px rgba(0, 176, 255, 0.35);
    }

    .btn-cyber-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 24px rgba(0, 176, 255, 0.5);
    }

    .btn-cyber-red {
      background: linear-gradient(135deg, #e51b24, #b71c1c);
      color: #fff;
      box-shadow: 0 4px 20px rgba(229, 27, 36, 0.35);
    }

    .btn-cyber-red:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 24px rgba(229, 27, 36, 0.5);
    }

    /* Telemetry widgets */
    .stat-widget {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 12px;
      padding: 14px 18px;
      margin-bottom: 12px;
    }

    .stat-widget .label {
      font-size: 11px;
      color: #8b92a5;
      text-transform: uppercase;
      font-weight: 700;
    }

    .stat-widget .value {
      font-size: 20px;
      font-weight: 900;
      color: #fff;
      margin-top: 4px;
      font-family: 'Roboto Mono', monospace;
    }
  </style>
</head>
<body>

  <div class="admin-container">
    <!-- NAVBAR -->
    <header class="admin-nav">
      <a href="index.php" class="brand-glow">
        <span class="icon">✈</span>
        <span class="title">AVIATOR PRO</span>
        <span class="badge">NEURAL AI</span>
      </a>

      <div style="display: flex; align-items: center; gap: 16px;">
        <div class="admin-status-badge">
          <span class="pulse-dot"></span>
          <span id="radarLinkStatus">RADAR LINK: 18ms (ACTIVE)</span>
        </div>

        <a href="index.php" style="color: #90caf9; text-decoration: none; font-size: 13px; font-weight: 700;">
          &larr; Return to Game
        </a>

        <?php if ($isAdmin): ?>
        <button id="btnAdminLogout" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 12px;">
          Log Out
        </button>
        <?php endif; ?>
      </div>
    </header>

    <?php if (!$isGateUnlocked): ?>
    <!-- 1. STEP 1: MASTER GATE LOCK SCREEN -->
    <div class="gate-lock-card">
      <div class="gate-icon">🔐</div>
      <h1 class="gate-title">RESTRICTED PORTAL</h1>
      <p class="gate-subtitle">
        Access to the Aviator Predictor and System Control Console is restricted. Enter the master security passkey to proceed.
      </p>

      <form id="formGateUnlock">
        <div style="text-align: left; margin-bottom: 8px;">
          <label style="font-size: 12px; font-weight: 700; color: #8b92a5;">Master Security Passcode</label>
        </div>
        <div style="position: relative;">
          <input type="password" id="gatePassInput" class="input-cyber" placeholder="Enter master password..." required autofocus style="padding-right: 42px;">
          <button type="button" class="btn-eye-toggle" onclick="togglePwdVisibility('gatePassInput', this)" title="Toggle Password Privacy" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #8b92a5; cursor: pointer; font-size: 16px; padding: 4px;">👁️</button>
        </div>
        <button type="submit" class="btn-cyber btn-cyber-primary" style="margin-top: 12px;">
          🔓 Unlock Security Gate
        </button>
      </form>
    </div>

    <?php elseif (!$isAdmin): ?>
    <!-- 2. STEP 2 & 3: REGISTER OR LOGIN ADMINISTRATOR -->
    <div class="gate-lock-card" style="max-width: 520px;">
      <div class="gate-icon">👑</div>
      <h1 class="gate-title">ADMINISTRATOR ACCESS</h1>
      <p class="gate-subtitle">
        Master passkey verified. Register an administrator account or sign in to activate the live predictor.
      </p>

      <div style="display: flex; background: rgba(0,0,0,0.4); border-radius: 8px; padding: 4px; margin-bottom: 20px;">
        <button id="btnTabAdminLogin" style="flex:1; padding: 10px; border:none; border-radius: 6px; font-weight: 700; cursor: pointer; background: var(--bg-hover); color:#fff;">
          Sign In
        </button>
        <button id="btnTabAdminRegister" style="flex:1; padding: 10px; border:none; border-radius: 6px; font-weight: 700; cursor: pointer; background: transparent; color:#8b92a5;">
          Register Admin
        </button>
      </div>

      <!-- Admin Login Form -->
      <form id="formAdminLogin">
        <div style="text-align: left;">
          <label style="font-size: 12px; font-weight: 700; color: #8b92a5;">Admin Username or Email</label>
          <input type="text" id="adminLoginUsername" class="input-cyber" placeholder="Admin username" required>
        </div>
        <div style="text-align: left; margin-top: 8px;">
          <label style="font-size: 12px; font-weight: 700; color: #8b92a5;">Admin Password</label>
          <div style="position: relative;">
            <input type="password" id="adminLoginPassword" class="input-cyber" placeholder="Enter password" required style="padding-right: 42px;">
            <button type="button" class="btn-eye-toggle" onclick="togglePwdVisibility('adminLoginPassword', this)" title="Toggle Password Privacy" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #8b92a5; cursor: pointer; font-size: 16px; padding: 4px;">👁️</button>
          </div>
        </div>
        <button type="submit" class="btn-cyber btn-cyber-primary" style="margin-top: 14px;">
          ⚡ Sign In to Predictor
        </button>
      </form>

      <!-- Admin Register Form -->
      <form id="formAdminRegister" style="display: none;">
        <div style="text-align: left;">
          <label style="font-size: 12px; font-weight: 700; color: #8b92a5;">Admin Username</label>
          <input type="text" id="adminRegUsername" class="input-cyber" placeholder="Choose admin handle" required>
        </div>
        <div style="text-align: left; margin-top: 8px;">
          <label style="font-size: 12px; font-weight: 700; color: #8b92a5;">Admin Email (Optional)</label>
          <input type="email" id="adminRegEmail" class="input-cyber" placeholder="admin@aviator.local">
        </div>
        <div style="text-align: left; margin-top: 8px;">
          <label style="font-size: 12px; font-weight: 700; color: #8b92a5;">Personal Admin Password</label>
          <div style="position: relative;">
            <input type="password" id="adminRegPassword" class="input-cyber" placeholder="At least 6 characters" required style="padding-right: 42px;">
            <button type="button" class="btn-eye-toggle" onclick="togglePwdVisibility('adminRegPassword', this)" title="Toggle Password Privacy" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #8b92a5; cursor: pointer; font-size: 16px; padding: 4px;">👁️</button>
          </div>
        </div>
        <input type="hidden" id="adminRegMasterKey" value="">
        <button type="submit" class="btn-cyber btn-cyber-red" style="margin-top: 14px;">
          🚀 Register Administrator (+50k Bankroll)
        </button>
      </form>
    </div>

    <?php else: ?>
    <!-- 3. STEP 4: ACTIVE AVIATOR PREDICTOR DASHBOARD (SEE OUTCOMES!) -->
    <div class="dashboard-grid">
      <!-- Left Column: Hero Predictor & Live Signal -->
      <section>
        <div class="predictor-hero">
          <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
              <span style="font-size: 12px; font-weight: 800; letter-spacing: 1.5px; color: var(--neon-cyan); text-transform: uppercase;">
                🔮 Aviator Neural Radar Predictor
              </span>
              <h2 style="font-size: 24px; font-weight: 900; margin: 4px 0 0 0;">
                ACTIVE FLIGHT SIGNAL
              </h2>
            </div>
            <div style="background: rgba(0, 229, 255, 0.1); border: 1px solid var(--neon-cyan); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; color: var(--neon-cyan);">
              AI V5.2 PRO
            </div>
          </div>

          <!-- Giant Target Multiplier -->
          <div class="target-multiplier-display">
            <span id="predTargetMult">--.--x</span>
            <span style="font-size: 18px; color: #8b92a5; font-weight: 600;">Target Multiplier</span>
          </div>

          <div style="font-size: 13px; color: #cbd5e1; margin-bottom: 12px;">
            Target Round ID: <strong id="predRoundUuid" style="color: var(--neon-cyan); font-family: monospace;">rnd_loading</strong>
          </div>

          <!-- Strategy Hint Banner -->
          <div id="predStrategyBox" style="background: rgba(0, 229, 255, 0.08); border-left: 3px solid #00e5ff; padding: 8px 12px; border-radius: 4px; font-size: 12px; margin-bottom: 16px; color: #e0f7fa;">
            💡 Strategy: <span id="predStrategyText">Connecting to flight radar signal...</span>
          </div>

          <!-- Signal Telemetry Pills -->
          <div class="signal-pills-row">
            <div class="signal-pill">
              <span>🎯 Confidence:</span>
              <strong id="predConfidence" style="color: var(--neon-green);">99.8%</strong>
            </div>
            <div class="signal-pill">
              <span>🛡️ Safe Cashout:</span>
              <strong id="predSafeCashout" style="color: var(--neon-gold);">--.--x</strong>
            </div>
            <div class="signal-pill">
              <span>⚠️ Volatility:</span>
              <strong id="predRiskLevel" style="color: #ff5252;">CALCULATING</strong>
            </div>
            <div class="signal-pill">
              <span>📡 Latency:</span>
              <span id="predLatency" style="color: #00e676; font-family: monospace;">18ms</span>
            </div>
          </div>

          <div style="margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="index.php" class="btn-cyber btn-cyber-primary" style="flex: 1; text-decoration: none;">
              🎮 Launch Aviator with Live Predictor HUD
            </a>
            <button id="btnManualRefresh" class="btn-cyber" style="width: auto; background: rgba(255,255,255,0.08); color: #fff;">
              🔄 Refresh Signal
            </button>
          </div>
        </div>

        <!-- UPCOMING FLIGHTS OUTCOMES MATRIX (NEXT 10 ROUNDS!) -->
        <div class="queue-card">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h3 style="font-size: 18px; font-weight: 800; margin: 0;">
                📊 UPCOMING OUTCOMES FORECAST (NEXT 10 FLIGHTS)
              </h3>
              <p style="font-size: 12px; color: #8b92a5; margin: 4px 0 0 0;">
                Cryptographically determined outcomes queue. Multipliers are calculated in advance.
              </p>
            </div>
            <span style="font-size: 12px; background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 6px;">
              Auto-Synced
            </span>
          </div>

          <table class="table-modern">
            <thead>
              <tr>
                <th>Flight #</th>
                <th>Round UUID</th>
                <th>Predicted Outcome</th>
                <th>Safe Cashout</th>
                <th>Confidence</th>
                <th>Signal Tag</th>
              </tr>
            </thead>
            <tbody id="queueTableBody">
              <tr>
                <td colspan="6" style="text-align: center; color: #8b92a5; padding: 20px;">
                  Connecting to Flight Radar...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Right Column: Radar Telemetry & Outcome Override Console -->
      <aside>
        <!-- Radar Cloud Link Card -->
        <div class="control-card" style="margin-bottom: 20px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:8px;">
              <span class="pulse-dot"></span>
              <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: #fff;">
                RADAR CLOUD LINK
              </h3>
            </div>
            <span style="font-size: 11px; background: rgba(0,230,118,0.15); color: #00e676; padding: 2px 8px; border-radius: 12px; font-weight: 700;">
              SYNCHRONIZED
            </span>
          </div>

          <div style="font-size: 12px; line-height: 1.6; color: #cbd5e1; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px;">
            <div>Algorithm: <strong style="color:#fff;">Provably Fair Engine</strong></div>
            <div>Channel: <strong style="color:#90caf9;">AV-PRO-RADAR-01</strong></div>
            <div>Active Core: <strong style="color:var(--neon-cyan);">Neural v5.2 AI</strong></div>
            <div>Sync Latency: <strong id="radarCardLatency" style="color:#00e676; font-family:monospace;">18ms</strong></div>
          </div>
        </div>

        <!-- Outcome Overrider Console -->
        <div class="control-card">
          <h3 style="font-size: 16px; font-weight: 800; margin: 0; color: #fff;">
            ⚡ OUTCOME OVERRIDE CONSOLE
          </h3>
          <p style="font-size: 12px; color: #8b92a5; margin: 4px 0 16px 0;">
            Force the crash multiplier for the next upcoming round.
          </p>

          <form id="formOverride">
            <label style="font-size: 12px; font-weight: 700; color: #8b92a5;">Next Round Multiplier (x)</label>
            <input type="number" id="overrideMultiplierInput" class="input-cyber" value="10.00" min="1.05" max="500.00" step="0.1" required>
            
            <div style="display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap;">
              <button type="button" class="chip-preset" data-val="1.20" style="padding: 4px 8px; font-size: 11px; background: rgba(255,255,255,0.08); border:none; border-radius: 4px; color:#fff; cursor:pointer;">1.20x (Bait)</button>
              <button type="button" class="chip-preset" data-val="2.50" style="padding: 4px 8px; font-size: 11px; background: rgba(255,255,255,0.08); border:none; border-radius: 4px; color:#fff; cursor:pointer;">2.50x (Med)</button>
              <button type="button" class="chip-preset" data-val="15.00" style="padding: 4px 8px; font-size: 11px; background: rgba(255,255,255,0.08); border:none; border-radius: 4px; color:#fff; cursor:pointer;">15.00x (High)</button>
              <button type="button" class="chip-preset" data-val="100.00" style="padding: 4px 8px; font-size: 11px; background: rgba(255,255,255,0.08); border:none; border-radius: 4px; color:#fff; cursor:pointer;">100x (Moon)</button>
            </div>

            <button type="submit" class="btn-cyber btn-cyber-red">
              🔒 Lock Next Round Outcome
            </button>
          </form>

          <div id="overrideFeedback" style="margin-top: 10px; font-size: 12px;"></div>
        </div>

        <!-- Telemetry Metrics Card -->
        <div class="control-card" style="margin-top: 24px;">
          <h3 style="font-size: 16px; font-weight: 800; margin: 0 0 16px 0; color: #fff;">
            📈 PLATFORM TELEMETRY
          </h3>

          <div class="stat-widget">
            <div class="label">Total Registered Pilots</div>
            <div id="statTotalUsers" class="value">--</div>
          </div>

          <div class="stat-widget">
            <div class="label">Total Game Rounds</div>
            <div id="statTotalRounds" class="value">--</div>
          </div>

          <div class="stat-widget">
            <div class="label">Total Wager Volume</div>
            <div id="statTotalVolume" class="value">-- KES</div>
          </div>

          <div class="stat-widget">
            <div class="label">Platform Net GGR</div>
            <div id="statNetGGR" class="value" style="color: var(--neon-green);">-- KES</div>
          </div>

          <div class="stat-widget">
            <div class="label">RTP Percentage</div>
            <div id="statRTP" class="value">--%</div>
          </div>
        </div>
      </aside>
    </div>
    <?php endif; ?>

  </div>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="toast-container"></div>

  <script>
    function showToast(message, type = 'info') {
      const container = document.getElementById('toastContainer');
      if (!container) return;
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;
      toast.innerHTML = `<span>${type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️')}</span><span>${message}</span>`;
      container.appendChild(toast);
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
      }, 3500);
    }

    function togglePwdVisibility(inputId, btn) {
      const el = document.getElementById(inputId);
      if (!el) return;
      if (el.type === 'password') {
        el.type = 'text';
        btn.style.color = '#00e5ff';
      } else {
        el.type = 'password';
        btn.style.color = '#8b92a5';
      }
    }

    // 1. Gate Unlock Handler
    const formGate = document.getElementById('formGateUnlock');
    if (formGate) {
      formGate.addEventListener('submit', async (e) => {
        e.preventDefault();
        const pwd = document.getElementById('gatePassInput').value;
        try {
          const res = await fetch('api/auth.php?action=admin_unlock_gate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: pwd })
          });
          const data = await res.json();
          if (data.success) {
            showToast('Master Passcode Accepted! Unlocking...', 'success');
            setTimeout(() => window.location.reload(), 600);
          } else {
            showToast(data.error || 'Access Denied', 'error');
          }
        } catch (e) {
          showToast('Connection error', 'error');
        }
      });
    }

    // 2. Tab switching between Admin Login & Admin Register
    const tabLogin = document.getElementById('btnTabAdminLogin');
    const tabRegister = document.getElementById('btnTabAdminRegister');
    const formLogin = document.getElementById('formAdminLogin');
    const formRegister = document.getElementById('formAdminRegister');

    if (tabLogin && tabRegister) {
      tabLogin.addEventListener('click', () => {
        tabLogin.style.background = 'var(--bg-hover)';
        tabLogin.style.color = '#fff';
        tabRegister.style.background = 'transparent';
        tabRegister.style.color = '#8b92a5';
        formLogin.style.display = 'block';
        formRegister.style.display = 'none';
      });

      tabRegister.addEventListener('click', () => {
        tabRegister.style.background = 'var(--bg-hover)';
        tabRegister.style.color = '#fff';
        tabLogin.style.background = 'transparent';
        tabLogin.style.color = '#8b92a5';
        formRegister.style.display = 'block';
        formLogin.style.display = 'none';
      });
    }

    // 3. Admin Registration Handler
    if (formRegister) {
      formRegister.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('adminRegUsername').value;
        const email = document.getElementById('adminRegEmail').value;
        const password = document.getElementById('adminRegPassword').value;
        const master_password = document.getElementById('adminRegMasterKey').value;

        try {
          const res = await fetch('api/auth.php?action=admin_register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, password, master_password })
          });
          const data = await res.json();
          if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 600);
          } else {
            showToast(data.error || 'Registration failed', 'error');
          }
        } catch (e) {
          showToast('Network error during registration', 'error');
        }
      });
    }

    // 4. Admin Login Handler
    if (formLogin) {
      formLogin.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('adminLoginUsername').value;
        const password = document.getElementById('adminLoginPassword').value;

        try {
          const res = await fetch('api/auth.php?action=admin_login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
          });
          const data = await res.json();
          if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 600);
          } else {
            showToast(data.error || 'Login failed', 'error');
          }
        } catch (e) {
          showToast('Network error during login', 'error');
        }
      });
    }

    // 5. Logout Handler
    const btnLogout = document.getElementById('btnAdminLogout');
    if (btnLogout) {
      btnLogout.addEventListener('click', async () => {
        await fetch('api/auth.php?action=logout');
        window.location.reload();
      });
    }

    // 6. Live Predictor Polling & Outcome Display
    async function loadPredictorData() {
      try {
        const res = await fetch('api/predictor.php?action=live');
        const data = await res.json();
        if (data.success && data.signal) {
          const sig = data.signal;
          const active = sig.active_round;
          if (active) {
            document.getElementById('predTargetMult').innerText = `${active.predicted_multiplier.toFixed(2)}x`;
            document.getElementById('predRoundUuid').innerText = active.round_uuid;
            document.getElementById('predConfidence').innerText = `${active.confidence}%`;
            document.getElementById('predSafeCashout').innerText = `${active.safe_cashout.toFixed(2)}x`;
            document.getElementById('predRiskLevel').innerText = active.risk_level;
            document.getElementById('predRiskLevel').style.color = active.risk_level === 'HIGH' || active.risk_level.includes('RISK') ? '#ff1744' : (active.risk_level === 'LOW RISK' ? '#00e676' : '#ffd600');
            
            // Strategy advice & radar latency
            const stratEl = document.getElementById('predStrategyText');
            if (stratEl) stratEl.innerText = active.strategy || 'Optimal safe flight pattern detected.';

            const lat = `${sig.sync_latency_ms || 18}ms`;
            const latEl = document.getElementById('predLatency');
            if (latEl) latEl.innerText = lat;
            const cLatEl = document.getElementById('radarCardLatency');
            if (cLatEl) cLatEl.innerText = lat;
            const linkStatusEl = document.getElementById('radarLinkStatus');
            if (linkStatusEl) linkStatusEl.innerText = `RADAR LINK: ${lat} (ACTIVE)`;
          }

          // Render upcoming queue table
          const tbody = document.getElementById('queueTableBody');
          if (tbody && sig.upcoming_queue) {
            let html = '';
            sig.upcoming_queue.forEach((q, idx) => {
              let badgeColor = 'badge-cyan';
              if (q.predicted_multiplier >= 10.0) badgeColor = 'badge-gold';
              else if (q.predicted_multiplier >= 3.0) badgeColor = 'badge-purple';
              else if (q.predicted_multiplier <= 1.25) badgeColor = 'badge-red';

              html += `
                <tr>
                  <td style="font-weight: 700; color: #8b92a5;">Flight #${idx + 1}</td>
                  <td style="font-family: monospace; font-size: 12px; color: #90caf9;">${q.round_uuid}</td>
                  <td><span class="mult-badge ${badgeColor}">${q.predicted_multiplier.toFixed(2)}x</span></td>
                  <td style="font-family: monospace; font-weight: 700; color: var(--neon-gold);">${q.safe_cashout.toFixed(2)}x</td>
                  <td style="color: var(--neon-green); font-weight: 600;">${q.confidence}%</td>
                  <td><span style="font-size: 11px; font-weight: 700; opacity: 0.9;">${q.badge}</span></td>
                </tr>
              `;
            });
            tbody.innerHTML = html;
          }
        }
      } catch (e) {
        console.warn('Predictor fetch error:', e);
      }
    }

    // 7. Load Telemetry Stats
    async function loadStats() {
      try {
        const res = await fetch('api/predictor.php?action=system_stats');
        const data = await res.json();
        if (data.success && data.stats) {
          const s = data.stats;
          document.getElementById('statTotalUsers').innerText = s.total_users.toLocaleString();
          document.getElementById('statTotalRounds').innerText = s.total_rounds.toLocaleString();
          document.getElementById('statTotalVolume').innerText = `${s.total_volume.toLocaleString()} KES`;
          document.getElementById('statNetGGR').innerText = `${s.house_net.toLocaleString()} KES`;
          document.getElementById('statRTP').innerText = `${s.rtp}%`;
        }
      } catch (e) {}
    }

    // 8. Manual Outcome Override Submit
    const formOverride = document.getElementById('formOverride');
    if (formOverride) {
      formOverride.addEventListener('submit', async (e) => {
        e.preventDefault();
        const mult = parseFloat(document.getElementById('overrideMultiplierInput').value);
        const feedback = document.getElementById('overrideFeedback');

        try {
          const res = await fetch('api/predictor.php?action=override', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ multiplier: mult })
          });
          const data = await res.json();
          if (data.success) {
            showToast(data.message, 'success');
            feedback.innerHTML = `<span style="color:var(--neon-green); font-weight:bold;">${data.message}</span>`;
            loadPredictorData();
          } else {
            showToast(data.error || 'Failed to lock multiplier', 'error');
            feedback.innerHTML = `<span style="color:var(--neon-red); font-weight:bold;">${data.error}</span>`;
          }
        } catch (e) {
          showToast('Override request failed', 'error');
        }
      });

      // Quick Preset Chips
      document.querySelectorAll('.chip-preset').forEach(btn => {
        btn.addEventListener('click', () => {
          document.getElementById('overrideMultiplierInput').value = btn.dataset.val;
        });
      });
    }

    // Manual Refresh button
    const btnRefresh = document.getElementById('btnManualRefresh');
    if (btnRefresh) {
      btnRefresh.addEventListener('click', () => {
        loadPredictorData();
        loadStats();
        showToast('Telemetry refreshed', 'info');
      });
    }

    // Auto-poll predictor every 2.5 seconds when active
    if (document.getElementById('predTargetMult')) {
      loadPredictorData();
      loadStats();
      setInterval(loadPredictorData, 2500);
      setInterval(loadStats, 10000);
    }
  </script>
</body>
</html>
