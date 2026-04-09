<?php
// settings.php — Full-page Settings for Map Aware Home
// Drop this file into your tenant/ or landlord/ folder.
// It auto-detects the role via $_SESSION and adjusts nav links accordingly.

session_start();

// Remove these mock values when integrating — your session is already set by login
if (!isset($_SESSION['username']))  $_SESSION['username']  = 'Juan Dela Cruz';
if (!isset($_SESSION['tenant_id'])) $_SESSION['tenant_id'] = 1;
// For landlord pages use $_SESSION['landlord_id'] instead

$current_page  = basename($_SERVER['PHP_SELF']);
$is_landlord   = isset($_SESSION['landlord_id']);
$is_tenant     = isset($_SESSION['tenant_id']);
$dashboard_url = $is_landlord ? 'landlord-properties.php' : 'tenant.php';
$logout_url    = '../LOGIN/logout.php';
$account_url   = 'account.php';
$edit_url      = 'edit-account.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings — Map Aware Home</title>
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="../css/bootstrap.min.css">

  <style>
    /* ===================================================
       RESET & BASE
    =================================================== */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --brand:        #8d0b41;
      --brand-dark:   #6a0831;
      --brand-light:  #fdf0f5;
      --text-primary: #1c1c1e;
      --text-muted:   #6b6b6e;
      --text-hint:    #ababae;
      --bg-page:      #f5f4f2;
      --bg-card:      #ffffff;
      --bg-hover:     #f0eeec;
      --border:       rgba(0,0,0,0.09);
      --border-med:   rgba(0,0,0,0.15);
      --radius-sm:    6px;
      --radius-md:    10px;
      --radius-lg:    14px;
      --shadow-card:  0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
      --font:         'DM Sans', sans-serif;
      --font-display: 'DM Serif Display', serif;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: var(--font);
      background: var(--bg-page);
      color: var(--text-primary);
      font-size: 15px;
      line-height: 1.6;
      min-height: 100vh;
    }

    a { text-decoration: none; color: inherit; }
    button { font-family: var(--font); cursor: pointer; }
    input, select, textarea { font-family: var(--font); }

    /* ===================================================
       TOP NAVIGATION HEADER
    =================================================== */
    header {
      position: sticky;
      top: 0;
      z-index: 1000;
      background: #fff;
      border-bottom: 0.5px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
      height: 62px;
      gap: 1.5rem;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 17px;
      font-weight: 600;
      color: var(--brand);
      white-space: nowrap;
      flex-shrink: 0;
    }
    .logo img { height: 32px; width: auto; }

    .nav-links {
      display: flex;
      align-items: center;
      list-style: none;
      gap: 2px;
      flex: 1;
      justify-content: center;
    }
    .nav-links li a {
      display: block;
      padding: 6px 14px;
      border-radius: var(--radius-sm);
      font-size: 14px;
      font-weight: 500;
      color: var(--text-muted);
      transition: background 0.15s, color 0.15s;
    }
    .nav-links li a:hover  { background: var(--bg-hover); color: var(--text-primary); }
    .nav-links li a.active { background: var(--brand-light); color: var(--brand); }

    .nav-icons {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
    }

    /* User dropdown */
    .nav-dropdown { position: relative; }
    .nav-dropdown-trigger {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: var(--radius-md);
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      color: var(--text-primary);
      border: 0.5px solid transparent;
      transition: background 0.15s, border-color 0.15s;
    }
    .nav-dropdown-trigger:hover { background: var(--bg-hover); border-color: var(--border); }
    .avatar-sm {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: var(--brand);
      display: flex; align-items: center; justify-content: center;
      color: white; font-size: 12px; font-weight: 600;
      flex-shrink: 0;
    }

    .nav-dropdown-menu {
      display: none;
      position: absolute;
      right: 0; top: calc(100% + 6px);
      background: #fff;
      border: 0.5px solid var(--border-med);
      border-radius: var(--radius-lg);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      min-width: 180px;
      overflow: hidden;
      z-index: 2000;
    }
    .nav-dropdown:hover .nav-dropdown-menu { display: block; }
    .nav-dropdown-menu a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      font-size: 14px;
      color: var(--text-primary);
      transition: background 0.12s;
    }
    .nav-dropdown-menu a i    { width: 16px; color: var(--text-muted); font-size: 13px; }
    .nav-dropdown-menu a:hover { background: var(--bg-hover); }
    .nav-dropdown-menu a.active-link { color: var(--brand); font-weight: 500; }
    .nav-dropdown-menu a.active-link i { color: var(--brand); }
    .nav-dropdown-menu .menu-divider { height: 0.5px; background: var(--border); margin: 4px 0; }

    /* Bell */
    .bell-btn {
      position: relative;
      width: 36px; height: 36px;
      border-radius: var(--radius-md);
      border: 0.5px solid transparent;
      background: transparent;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      color: var(--text-muted);
      transition: background 0.15s, border-color 0.15s;
    }
    .bell-btn:hover { background: var(--bg-hover); border-color: var(--border); color: var(--text-primary); }
    .bell-badge {
      position: absolute;
      top: 5px; right: 5px;
      width: 7px; height: 7px;
      background: var(--brand);
      border-radius: 50%;
      border: 1.5px solid #fff;
    }

    /* Bell dropdown */
    .bell-wrapper {
      position: relative;
    }
    .bell-dropdown {
      display: none;
      position: absolute;
      right: 0; top: calc(100% + 6px);
      background: #fff;
      border: 0.5px solid var(--border-med);
      border-radius: var(--radius-lg);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      min-width: 280px;
      overflow: hidden;
      z-index: 2000;
    }
    .bell-wrapper:hover .bell-dropdown { display: block; }
    .bell-dropdown-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px 8px;
    }
    .bell-dropdown-header span  { font-size: 13px; font-weight: 600; }
    .bell-dropdown-header button {
      background: none; border: none;
      font-size: 12px; color: var(--brand);
      font-family: var(--font); font-weight: 500;
    }
    .bell-divider { height: 0.5px; background: var(--border); }
    .bell-empty {
      padding: 16px;
      font-size: 13px;
      color: var(--text-muted);
      text-align: center;
    }

    /* Hamburger — mobile only */
    #navmenu {
      display: none;
      width: 36px; height: 36px;
      border-radius: var(--radius-md);
      background: transparent;
      border: none;
      cursor: pointer;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
      font-size: 18px;
    }

    /* Nav overlay */
    #navOverlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 999;
    }
    #navOverlay.active { display: block; }

    /* Sidebar toggle — mobile only */
    .sidebar-toggle-btn {
      display: none;
      width: 36px; height: 36px;
      border-radius: var(--radius-md);
      border: 0.5px solid var(--border-med);
      background: var(--bg-card);
      cursor: pointer;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      color: var(--text-muted);
    }

    /* ===================================================
       SETTINGS LAYOUT
    =================================================== */
    .settings-page {
      display: flex;
      min-height: calc(100vh - 62px);
    }

    /* LEFT SIDEBAR */
    .settings-sidebar {
      width: 240px;
      flex-shrink: 0;
      background: var(--bg-card);
      border-right: 0.5px solid var(--border);
      padding: 1.5rem 0;
      position: sticky;
      top: 62px;
      height: calc(100vh - 62px);
      overflow-y: auto;
    }

    .sidebar-section-label {
      padding: 0 1.25rem 6px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      color: var(--text-hint);
      margin-top: 1.25rem;
    }
    .sidebar-section-label:first-child { margin-top: 0; }

    .sidebar-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 1.25rem;
      font-size: 14px;
      font-weight: 400;
      color: var(--text-muted);
      cursor: pointer;
      border-left: 2px solid transparent;
      transition: all 0.15s;
    }
    .sidebar-item i      { width: 18px; font-size: 13px; text-align: center; }
    .sidebar-item:hover  { background: var(--bg-hover); color: var(--text-primary); }
    .sidebar-item.active {
      background: var(--brand-light);
      color: var(--brand);
      font-weight: 500;
      border-left-color: var(--brand);
    }
    .sidebar-item.active i { color: var(--brand); }

    .sidebar-item.is-danger      { color: #c0392b; }
    .sidebar-item.is-danger i    { color: #c0392b; }
    .sidebar-item.is-danger:hover { background: rgba(192,57,43,0.06); }
    .sidebar-item.is-danger.active { background: rgba(192,57,43,0.08); border-left-color: #c0392b; }

    /* MAIN CONTENT */
    .settings-content {
      flex: 1;
      padding: 2.5rem;
      max-width: 760px;
    }

    .section-header { margin-bottom: 1.75rem; }
    .section-header h1 { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-bottom: 3px; }
    .section-header p  { font-size: 14px; color: var(--text-muted); }

    /* ===================================================
       CARDS
    =================================================== */
    .card {
      background: var(--bg-card);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-card);
      overflow: hidden;
      margin-bottom: 1.25rem;
    }
    .card.card-danger { border-color: rgba(192,57,43,0.2); }

    .card-header {
      padding: 1rem 1.25rem;
      border-bottom: 0.5px solid var(--border);
    }
    .card-header h2 { font-size: 14px; font-weight: 600; color: var(--text-primary); }
    .card-header p  { font-size: 13px; color: var(--text-muted); margin-top: 2px; }

    .card-footer {
      padding: 0.875rem 1.25rem;
      border-top: 0.5px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      background: var(--bg-page);
    }

    /* ===================================================
       FORM FIELDS
    =================================================== */
    .field-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
    }
    .field-grid.one-col { grid-template-columns: 1fr; }

    .field-cell {
      padding: 1rem 1.25rem;
      border-bottom: 0.5px solid var(--border);
      border-right: 0.5px solid var(--border);
    }
    .field-cell:nth-child(even) { border-right: none; }
    .field-cell.full {
      grid-column: 1 / -1;
      border-right: none;
    }
    /* Remove bottom border from last row */
    .field-grid:not(.one-col) .field-cell:nth-last-child(1),
    .field-grid:not(.one-col) .field-cell:nth-last-child(2):not(.full) {
      border-bottom: none;
    }
    .field-grid.one-col .field-cell:last-child { border-bottom: none; }
    .field-cell.full:last-child { border-bottom: none; }

    .field-label {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-hint);
      margin-bottom: 5px;
      display: block;
    }
    .field-input {
      width: 100%;
      padding: 8px 10px;
      border: 0.5px solid var(--border-med);
      border-radius: var(--radius-sm);
      font-size: 14px;
      color: var(--text-primary);
      background: var(--bg-card);
      transition: border-color 0.15s, box-shadow 0.15s;
    }
    .field-input:focus {
      outline: none;
      border-color: var(--brand);
      box-shadow: 0 0 0 3px rgba(141,11,65,0.08);
    }
    .field-input:disabled {
      background: var(--bg-page);
      color: var(--text-hint);
      cursor: not-allowed;
    }

    select.field-input {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='%236b6b6e'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 30px;
    }

    textarea.field-input { resize: vertical; min-height: 80px; line-height: 1.5; }

    /* ===================================================
       BUTTONS
    =================================================== */
    .btn {
      padding: 8px 16px;
      border-radius: var(--radius-sm);
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      border: 0.5px solid var(--border-med);
      background: var(--bg-card);
      color: var(--text-primary);
      transition: background 0.15s, transform 0.1s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn:hover  { background: var(--bg-hover); }
    .btn:active { transform: scale(0.98); }

    .btn-primary { background: var(--brand); color: white; border-color: var(--brand); }
    .btn-primary:hover { background: var(--brand-dark); }

    .btn-danger { color: #c0392b; border-color: rgba(192,57,43,0.3); }
    .btn-danger:hover { background: rgba(192,57,43,0.06); }

    .btn-sm { padding: 6px 12px; font-size: 13px; }

    /* ===================================================
       TOGGLE SWITCH
    =================================================== */
    .toggle-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.875rem 1.25rem;
      border-bottom: 0.5px solid var(--border);
    }
    .toggle-row:last-child { border-bottom: none; }
    .toggle-info h4 { font-size: 14px; font-weight: 500; color: var(--text-primary); margin-bottom: 1px; }
    .toggle-info p  { font-size: 13px; color: var(--text-muted); }

    .toggle {
      position: relative;
      width: 40px; height: 22px;
      flex-shrink: 0;
    }
    .toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
    .toggle-slider {
      position: absolute; inset: 0;
      background: #ddd;
      border-radius: 22px;
      cursor: pointer;
      transition: 0.2s;
    }
    .toggle-slider:before {
      content: '';
      position: absolute;
      height: 16px; width: 16px;
      left: 3px; bottom: 3px;
      background: white;
      border-radius: 50%;
      transition: 0.2s;
      box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle input:checked + .toggle-slider { background: var(--brand); }
    .toggle input:checked + .toggle-slider:before { transform: translateX(18px); }

    /* ===================================================
       AVATAR
    =================================================== */
    .avatar-row {
      display: flex;
      align-items: center;
      gap: 1.25rem;
      padding: 1.25rem;
    }
    .avatar-circle {
      width: 72px; height: 72px;
      border-radius: 50%;
      background: var(--brand);
      display: flex; align-items: center; justify-content: center;
      font-family: var(--font-display);
      font-size: 26px;
      color: white;
      flex-shrink: 0;
      border: 3px solid var(--brand-light);
      background-size: cover;
      background-position: center;
    }
    .avatar-name { font-size: 16px; font-weight: 600; }
    .avatar-info p { font-size: 13px; color: var(--text-muted); margin-top: 3px; line-height: 1.4; }

    /* ===================================================
       SESSION LIST
    =================================================== */
    .session-row {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0.875rem 1.25rem;
      border-bottom: 0.5px solid var(--border);
    }
    .session-row:last-child { border-bottom: none; }
    .session-icon {
      width: 36px; height: 36px;
      border-radius: var(--radius-sm);
      background: var(--bg-page);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      color: var(--text-muted);
      font-size: 14px;
    }
    .session-info { flex: 1; }
    .session-info h4 { font-size: 14px; font-weight: 500; }
    .session-info p  { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
    .badge-active {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 2px 8px; border-radius: 20px;
      background: rgba(39,174,96,0.1);
      color: #1a7a3c;
      font-size: 12px; font-weight: 500;
      white-space: nowrap;
    }
    .badge-dot { width: 6px; height: 6px; border-radius: 50%; background: #27ae60; }

    /* ===================================================
       LEGAL LIST
    =================================================== */
    .legal-row {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0.875rem 1.25rem;
      border-bottom: 0.5px solid var(--border);
      cursor: pointer;
      transition: background 0.12s;
    }
    .legal-row:last-child { border-bottom: none; }
    .legal-row:hover { background: var(--bg-hover); }
    .legal-icon {
      width: 36px; height: 36px;
      border-radius: var(--radius-sm);
      background: var(--brand);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      color: white; font-size: 14px;
    }
    .legal-text h4 { font-size: 14px; font-weight: 500; }
    .legal-text p  { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
    .legal-arrow   { margin-left: auto; color: var(--text-hint); font-size: 12px; }

    /* ===================================================
       DANGER ROWS
    =================================================== */
    .danger-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 1.25rem;
      border-bottom: 0.5px solid var(--border);
      gap: 1rem;
    }
    .danger-row:last-child { border-bottom: none; }
    .danger-row h4 { font-size: 14px; font-weight: 500; margin-bottom: 2px; }
    .danger-row p  { font-size: 13px; color: var(--text-muted); }

    /* ===================================================
       ALERTS
    =================================================== */
    .alert {
      padding: 10px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      margin: 1rem 1.25rem 0;
      display: none;
    }
    .alert.show  { display: block; }
    .alert-success { background: #edfaf3; color: #1a6636; border: 0.5px solid #b0e0c4; }
    .alert-error   { background: #fdf2f2; color: #8b1a1a; border: 0.5px solid #f2b8b8; }

    /* Password strength */
    .strength-bar { height: 4px; border-radius: 2px; margin-top: 6px; background: #eee; overflow: hidden; }
    .strength-fill { height: 100%; border-radius: 2px; width: 0; transition: width 0.3s, background 0.3s; }
    .strength-label { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

    /* ===================================================
       SECTION VISIBILITY
    =================================================== */
    .settings-section         { display: none; }
    .settings-section.visible { display: block; }

    /* ===================================================
       LEGAL MODAL
    =================================================== */
    .legal-modal-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.55);
      z-index: 5000;
      align-items: center;
      justify-content: center;
    }
    .legal-modal-overlay.active { display: flex; }

    .legal-modal {
      background: var(--bg-card);
      border-radius: var(--radius-lg);
      width: 90%; max-width: 680px; max-height: 85vh;
      display: flex; flex-direction: column;
      box-shadow: 0 20px 60px rgba(0,0,0,0.25);
      animation: modalUp 0.2s ease;
    }
    @keyframes modalUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .legal-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 1.5rem;
      border-bottom: 0.5px solid var(--border);
    }
    .legal-modal-header h3 { font-size: 16px; font-weight: 600; }

    .legal-modal-close {
      width: 32px; height: 32px;
      border-radius: 50%;
      border: none;
      background: var(--bg-page);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px;
      color: var(--text-muted);
      transition: background 0.15s;
    }
    .legal-modal-close:hover { background: var(--bg-hover); color: var(--text-primary); }

    .legal-modal-body {
      padding: 1.5rem;
      overflow-y: auto;
      flex: 1;
      font-size: 14px;
      line-height: 1.7;
    }
    .legal-modal-body h1 { font-size: 20px; font-weight: 600; margin-bottom: 1rem; border-bottom: 0.5px solid var(--border); padding-bottom: 0.75rem; }
    .legal-modal-body h2 { font-size: 15px; font-weight: 600; margin: 1.25rem 0 0.5rem; }
    .legal-modal-body p  { margin-bottom: 0.75rem; color: var(--text-muted); }
    .legal-modal-body ul { margin-left: 1.25rem; margin-bottom: 0.75rem; }
    .legal-modal-body li { margin-bottom: 5px; color: var(--text-muted); }

    .legal-modal-footer {
      padding: 1rem 1.5rem;
      border-top: 0.5px solid var(--border);
      display: flex;
      justify-content: flex-end;
    }

    /* ===================================================
       CONFIRM DIALOG
    =================================================== */
    .confirm-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.55);
      z-index: 6000;
      align-items: center;
      justify-content: center;
    }
    .confirm-overlay.active { display: flex; }

    .confirm-box {
      background: var(--bg-card);
      border-radius: var(--radius-lg);
      width: 90%; max-width: 400px;
      padding: 1.75rem;
      box-shadow: 0 20px 60px rgba(0,0,0,0.25);
      animation: modalUp 0.2s ease;
    }
    .confirm-icon { font-size: 28px; color: #e74c3c; margin-bottom: 0.75rem; }
    .confirm-box h3 { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
    .confirm-box p  { font-size: 14px; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.25rem; }
    .confirm-actions { display: flex; gap: 8px; justify-content: flex-end; }

    /* ===================================================
       MOBILE RESPONSIVE
    =================================================== */
    @media (max-width: 720px) {
      header { padding: 0 1rem; }
      .nav-links { display: none !important; }
      #navmenu { display: flex !important; }
      .nav-username { display: none; }

      /* Slide-in mobile nav */
      header .nav-links {
        position: fixed !important;
        top: 0 !important; right: -100% !important;
        width: 72% !important; height: 100vh !important;
        background: linear-gradient(160deg, var(--brand) 0%, var(--brand-dark) 100%) !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 72px 0 20px !important;
        transition: right 0.3s ease !important;
        z-index: 1001 !important;
        box-shadow: -6px 0 20px rgba(0,0,0,0.25) !important;
        display: flex !important;
        overflow-y: auto !important;
        list-style: none !important;
        gap: 0 !important;
      }
      header .nav-links.active { right: 0 !important; }
      header .nav-links li { width: 100% !important; }
      header .nav-links li a {
        display: block !important;
        padding: 13px 1.5rem !important;
        color: rgba(255,255,255,0.8) !important;
        border-radius: 0 !important;
        font-size: 15px !important;
        border-bottom: 0.5px solid rgba(255,255,255,0.1) !important;
      }
      header .nav-links li a:hover,
      header .nav-links li a.active {
        background: rgba(255,255,255,0.12) !important;
        color: white !important;
      }

      /* Settings sidebar overlay on mobile */
      .settings-sidebar {
        display: none;
        position: fixed;
        top: 62px; left: 0;
        height: calc(100vh - 62px);
        z-index: 900;
        box-shadow: 4px 0 20px rgba(0,0,0,0.12);
      }
      .settings-sidebar.open { display: block; }

      .sidebar-toggle-btn { display: flex !important; }

      .settings-content { padding: 1.25rem 1rem; max-width: 100%; }

      .field-grid { grid-template-columns: 1fr !important; }
      .field-cell { border-right: none !important; }
      .field-grid .field-cell:nth-last-child(2) { border-bottom: 0.5px solid var(--border) !important; }
    }

    @media (max-width: 900px) {
      .settings-content { padding: 1.5rem 1.25rem; }
    }
  </style>
</head>

<body>

<!-- =====================================================================
     HEADER
===================================================================== -->
<header>
  <a href="<?= $dashboard_url ?>" class="logo">
    <img src="../img/new_logo.png" alt="Logo">
    Map Aware Home
  </a>

  <ul class="nav-links">
    <?php if ($is_landlord): ?>
      <li><a href="landlord-properties.php" class="<?= $current_page=='landlord-properties.php'?'active':'' ?>">Properties</a></li>
      <li><a href="history.php"             class="<?= $current_page=='history.php'?'active':'' ?>">Rentals</a></li>
      <li><a href="landlord-map.php"        class="<?= $current_page=='landlord-map.php'?'active':'' ?>">Map</a></li>
      <li><a href="landlord-message.php"    class="<?= $current_page=='landlord-message.php'?'active':'' ?>">Messages</a></li>
      <li><a href="support.php"             class="<?= $current_page=='support.php'?'active':'' ?>">Support</a></li>
    <?php else: ?>
      <li><a href="tenant.php"          class="<?= $current_page=='tenant.php'?'active':'' ?>">Home</a></li>
      <li><a href="tenant-rental.php"   class="<?= $current_page=='tenant-rental.php'?'active':'' ?>">My Rental</a></li>
      <li><a href="tenant-map.php"      class="<?= $current_page=='tenant-map.php'?'active':'' ?>">Map</a></li>
      <li><a href="tenant-messages.php" class="<?= $current_page=='tenant-messages.php'?'active':'' ?>">Messages</a></li>
      <li><a href="support.php"         class="<?= $current_page=='support.php'?'active':'' ?>">Support</a></li>
    <?php endif; ?>
  </ul>

  <div class="nav-icons">

    <!-- User dropdown -->
    <div class="nav-dropdown">
      <div class="nav-dropdown-trigger">
        <div class="avatar-sm"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
        <span class="nav-username" style="font-size:14px;font-weight:500"><?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))) ?></span>
        <i class="fa-solid fa-chevron-down" style="font-size:11px;color:var(--text-muted)"></i>
      </div>
      <div class="nav-dropdown-menu">
        <a href="<?= $account_url ?>"><i class="fa-regular fa-id-card"></i> Account</a>
        <a href="settings.php" class="active-link"><i class="fa-solid fa-gear"></i> Settings</a>
        <div class="menu-divider"></div>
        <a href="<?= $logout_url ?>"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
      </div>
    </div>

    <!-- Notification bell -->
    <div class="bell-wrapper">
      <button class="bell-btn">
        <i class="fa-solid fa-bell" style="font-size:16px"></i>
        <span class="bell-badge" id="bellBadge" style="display:none"></span>
      </button>
      <div class="bell-dropdown">
        <div class="bell-dropdown-header">
          <span>Notifications</span>
          <button id="clearNotifications">Clear all</button>
        </div>
        <div class="bell-divider"></div>
        <div id="notificationList">
          <div class="bell-empty">No notifications</div>
        </div>
      </div>
    </div>

    <!-- Sidebar menu toggle (mobile) -->
    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Settings menu">
      <i class="fa-solid fa-sliders"></i>
    </button>

    <!-- Hamburger (mobile) -->
    <button id="navmenu">
      <i class="fa-solid fa-bars"></i>
    </button>

  </div>
</header>

<!-- Mobile nav overlay -->
<div id="navOverlay"></div>

<!-- =====================================================================
     SETTINGS PAGE
===================================================================== -->
<div class="settings-page">

  <!-- SIDEBAR -->
  <aside class="settings-sidebar" id="settingsSidebar">

    <div class="sidebar-section-label">Account</div>
    <div class="sidebar-item active" data-section="profile">
      <i class="fa-regular fa-circle-user"></i> Profile
    </div>
    <div class="sidebar-item" data-section="password">
      <i class="fa-solid fa-lock"></i> Password &amp; Security
    </div>
    <div class="sidebar-item" data-section="sessions">
      <i class="fa-solid fa-laptop"></i> Active Sessions
    </div>

    <div class="sidebar-section-label">Preferences</div>
    <div class="sidebar-item" data-section="notifications">
      <i class="fa-regular fa-bell"></i> Notifications
    </div>
    <div class="sidebar-item" data-section="privacy">
      <i class="fa-solid fa-shield-halved"></i> Privacy &amp; Data
    </div>
    <div class="sidebar-item" data-section="appearance">
      <i class="fa-solid fa-palette"></i> Appearance
    </div>

    <div class="sidebar-section-label">Legal</div>
    <div class="sidebar-item" data-section="legal">
      <i class="fa-solid fa-scale-balanced"></i> Legal &amp; Guidelines
    </div>

    <div class="sidebar-section-label" style="color:rgba(192,57,43,0.7)">Danger</div>
    <div class="sidebar-item is-danger" data-section="danger">
      <i class="fa-solid fa-triangle-exclamation"></i> Danger Zone
    </div>

  </aside>

  <!-- MAIN CONTENT -->
  <main class="settings-content">

    <!-- ============================================================
         PROFILE
    ============================================================ -->
    <div class="settings-section visible" id="sec-profile">
      <div class="section-header">
        <h1>Profile</h1>
        <p>Manage your personal information visible on the platform</p>
      </div>

      <div class="card">
        <div class="avatar-row">
          <div class="avatar-circle" id="avatarCircle">
            <?= strtoupper(substr($_SESSION['username'], 0, 2)) ?>
          </div>
          <div class="avatar-info">
            <div class="avatar-name"><?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))) ?></div>
            <p>JPG, PNG or GIF · Max 5 MB · Recommended 200×200px</p>
            <div style="display:flex;gap:8px;margin-top:8px">
              <button class="btn btn-sm" onclick="document.getElementById('avatarInput').click()">
                <i class="fa-solid fa-camera"></i> Change photo
              </button>
              <button class="btn btn-sm btn-danger" id="removeAvatarBtn">Remove</button>
            </div>
            <input type="file" id="avatarInput" accept="image/*" style="display:none">
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Personal information</h2>
          <p>Your name, email, and contact details</p>
        </div>
        <div id="profileAlert" class="alert"></div>
        <form id="profileForm">
          <div class="field-grid">
            <div class="field-cell">
              <label class="field-label">First name</label>
              <input class="field-input" type="text" name="first_name" value="Juan" required>
            </div>
            <div class="field-cell">
              <label class="field-label">Last name</label>
              <input class="field-input" type="text" name="last_name" value="Dela Cruz" required>
            </div>
            <div class="field-cell full">
              <label class="field-label">Email address</label>
              <input class="field-input" type="email" name="email" value="juan@email.com" required>
            </div>
            <div class="field-cell">
              <label class="field-label">Phone number</label>
              <input class="field-input" type="tel" name="phone" value="+63 912 345 6789">
            </div>
            <div class="field-cell">
              <label class="field-label">Role</label>
              <input class="field-input" type="text" value="<?= $is_landlord ? 'Landlord' : 'Tenant' ?>" disabled>
            </div>
          </div>
          <div class="card-footer">
            <button type="button" class="btn" onclick="this.closest('form').reset()">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="fa-regular fa-floppy-disk"></i> Save changes
            </button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Address</h2>
          <p>Your current residential address</p>
        </div>
        <form id="addressForm">
          <div class="field-grid">
            <div class="field-cell full">
              <label class="field-label">Street / unit / building</label>
              <input class="field-input" type="text" name="street" placeholder="e.g. 123 Rizal St., Brgy. Poblacion">
            </div>
            <div class="field-cell">
              <label class="field-label">City / municipality</label>
              <input class="field-input" type="text" name="city" placeholder="e.g. Quezon City">
            </div>
            <div class="field-cell">
              <label class="field-label">Province / region</label>
              <input class="field-input" type="text" name="province" placeholder="e.g. Metro Manila">
            </div>
            <div class="field-cell">
              <label class="field-label">ZIP code</label>
              <input class="field-input" type="text" name="zip" placeholder="e.g. 1100" maxlength="10">
            </div>
            <div class="field-cell">
              <label class="field-label">Country</label>
              <select class="field-input" name="country">
                <option selected>Philippines</option>
                <option>Other</option>
              </select>
            </div>
          </div>
          <div class="card-footer">
            <button type="button" class="btn" onclick="this.closest('form').reset()">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="fa-regular fa-floppy-disk"></i> Save address
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================
         PASSWORD & SECURITY
    ============================================================ -->
    <div class="settings-section" id="sec-password">
      <div class="section-header">
        <h1>Password &amp; Security</h1>
        <p>Protect your account with a strong password and extra verification steps</p>
      </div>

      <div class="card">
        <div class="card-header"><h2>Change password</h2></div>
        <div id="passwordAlert" class="alert"></div>
        <form id="changePasswordForm">
          <div class="field-grid one-col">
            <div class="field-cell full">
              <label class="field-label">Current password</label>
              <input class="field-input" type="password" id="currentPassword" name="current_password" placeholder="Enter your current password" required>
            </div>
            <div class="field-cell full">
              <label class="field-label">New password</label>
              <input class="field-input" type="password" id="newPassword" name="new_password" placeholder="At least 8 characters" required minlength="8">
              <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
              <div class="strength-label" id="strengthLabel"></div>
            </div>
            <div class="field-cell full">
              <label class="field-label">Confirm new password</label>
              <input class="field-input" type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter new password" required>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary" id="submitPasswordBtn">
              <i class="fa-solid fa-key"></i> Update password
            </button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Two-factor authentication</h2>
          <p>Adds a second verification step when you sign in</p>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Authenticator app (TOTP)</h4><p>Use Google Authenticator, Authy, or similar apps</p></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>SMS verification</h4><p>Receive a one-time code via text message</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Email verification</h4><p>Receive a one-time code via email</p></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>Recent login activity</h2></div>
        <div class="session-row">
          <div class="session-icon"><i class="fa-solid fa-desktop"></i></div>
          <div class="session-info"><h4>Chrome on Windows 11</h4><p>Quezon City, PH &mdash; Mar 31, 2026 10:24 AM</p></div>
          <span class="badge-active"><span class="badge-dot"></span> Current</span>
        </div>
        <div class="session-row">
          <div class="session-icon"><i class="fa-solid fa-mobile-screen"></i></div>
          <div class="session-info"><h4>Safari on iPhone 15</h4><p>Quezon City, PH &mdash; Mar 29, 2026 3:11 PM</p></div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         ACTIVE SESSIONS
    ============================================================ -->
    <div class="settings-section" id="sec-sessions">
      <div class="section-header">
        <h1>Active Sessions</h1>
        <p>All devices currently signed into your account</p>
      </div>

      <div class="card">
        <div class="session-row">
          <div class="session-icon"><i class="fa-solid fa-desktop"></i></div>
          <div class="session-info"><h4>Chrome &middot; Windows 11</h4><p>Quezon City, PH &mdash; Active now</p></div>
          <span class="badge-active"><span class="badge-dot"></span> Active</span>
        </div>
        <div class="session-row">
          <div class="session-icon"><i class="fa-solid fa-mobile-screen"></i></div>
          <div class="session-info"><h4>Safari &middot; iPhone 15</h4><p>Quezon City, PH &mdash; 2 days ago</p></div>
          <button class="btn btn-sm btn-danger" onclick="confirmRevoke(this,'Safari &middot; iPhone 15')">Revoke</button>
        </div>
        <div class="session-row">
          <div class="session-icon"><i class="fa-solid fa-tablet-screen-button"></i></div>
          <div class="session-info"><h4>Chrome &middot; Android tablet</h4><p>Makati, PH &mdash; 5 days ago</p></div>
          <button class="btn btn-sm btn-danger" onclick="confirmRevoke(this,'Chrome &middot; Android tablet')">Revoke</button>
        </div>
      </div>

      <div style="text-align:right">
        <button class="btn btn-danger"
          onclick="openConfirm('Sign out all devices?','This will log you out of all devices except the current one.',()=>alert('Signed out of all other sessions.'))">
          <i class="fa-solid fa-right-from-bracket"></i> Sign out all other sessions
        </button>
      </div>
    </div>

    <!-- ============================================================
         NOTIFICATIONS
    ============================================================ -->
    <div class="settings-section" id="sec-notifications">
      <div class="section-header">
        <h1>Notifications</h1>
        <p>Choose what updates you receive and how you receive them</p>
      </div>

      <div class="card">
        <div class="card-header"><h2>In-app notifications</h2></div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>New messages</h4><p>When someone sends you a message in chat</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Rent due reminders</h4><p>Alerts before your rent payment date</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Application updates</h4><p>Status changes on submitted rental applications</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>System announcements</h4><p>Platform updates and scheduled maintenance notices</p></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>Email notifications</h2></div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Weekly activity summary</h4><p>Digest of your rental activity every Monday</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Payment reminders</h4><p>Email 3 days before rent is due</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Property recommendations</h4><p>New listings that match your preferences</p></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Marketing &amp; promotions</h4><p>Tips, news, and feature announcements</p></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>Push notifications</h2><p>Requires browser or app permission</p></div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Enable push notifications</h4><p>Get real-time alerts even when the tab is closed</p></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PRIVACY & DATA
    ============================================================ -->
    <div class="settings-section" id="sec-privacy">
      <div class="section-header">
        <h1>Privacy &amp; Data</h1>
        <p>Control how your information is used and who can see it</p>
      </div>

      <div class="card">
        <div class="card-header"><h2>Profile visibility</h2></div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Show profile to <?= $is_landlord ? 'tenants' : 'landlords' ?></h4><p>They can see your name and contact when you interact</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Show online status</h4><p>Let others see when you were last active</p></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Allow search by name</h4><p>Other users can find your account by searching your name</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>Location &amp; map data</h2></div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Allow location access</h4><p>Used to show nearby properties and calculate distances</p></div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="toggle-row">
          <div class="toggle-info"><h4>Save location history</h4><p>Remember your location for faster map loading</p></div>
          <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>Your data</h2></div>
        <div class="danger-row">
          <div><h4>Download my data</h4><p>Export a copy of all your account data as a CSV file</p></div>
          <button class="btn btn-sm"><i class="fa-solid fa-download"></i> Request export</button>
        </div>
        <div class="danger-row">
          <div><h4>Clear activity history</h4><p>Remove your browsing and search history within the platform</p></div>
          <button class="btn btn-sm btn-danger"
            onclick="openConfirm('Clear activity history?','Your in-platform browsing history will be permanently removed.',()=>alert('Activity history cleared.'))">
            Clear history
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================
         APPEARANCE
    ============================================================ -->
    <div class="settings-section" id="sec-appearance">
      <div class="section-header">
        <h1>Appearance</h1>
        <p>Customize how Map Aware Home looks for you</p>
      </div>

      <div class="card">
        <div class="card-header"><h2>Theme</h2></div>
        <div class="toggle-row">
          <div class="toggle-info">
            <h4>Dark mode</h4>
            <p>Use a dark color scheme (coming soon)</p>
          </div>
          <label class="toggle"><input type="checkbox" disabled><span class="toggle-slider" style="opacity:0.4"></span></label>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>Language &amp; region</h2></div>
        <div class="field-grid">
          <div class="field-cell">
            <label class="field-label">Language</label>
            <select class="field-input">
              <option selected>English</option>
              <option>Filipino</option>
            </select>
          </div>
          <div class="field-cell">
            <label class="field-label">Date format</label>
            <select class="field-input">
              <option selected>MM/DD/YYYY</option>
              <option>DD/MM/YYYY</option>
              <option>YYYY-MM-DD</option>
            </select>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> Save preferences</button>
        </div>
      </div>
    </div>

    <!-- ============================================================
         LEGAL & GUIDELINES
    ============================================================ -->
    <div class="settings-section" id="sec-legal">
      <div class="section-header">
        <h1>Legal &amp; Guidelines</h1>
        <p>Review the terms, rules, and policies governing use of this platform</p>
      </div>

      <div class="card">
        <div class="legal-row" onclick="openLegal('terms')">
          <div class="legal-icon"><i class="fa-solid fa-file-contract"></i></div>
          <div class="legal-text"><h4>Terms &amp; Conditions</h4><p>Last updated December 2025</p></div>
          <div class="legal-arrow"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
        <div class="legal-row" onclick="openLegal('rental')">
          <div class="legal-icon"><i class="fa-solid fa-house-circle-check"></i></div>
          <div class="legal-text"><h4>Rules Related to Renting</h4><p>Guidelines for tenants and landlords</p></div>
          <div class="legal-arrow"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
        <div class="legal-row" onclick="openLegal('contract')">
          <div class="legal-icon"><i class="fa-solid fa-file-signature"></i></div>
          <div class="legal-text"><h4>Legal Contract Guidelines</h4><p>Important legal information for all users</p></div>
          <div class="legal-arrow"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
        <div class="legal-row" onclick="openLegal('conduct')">
          <div class="legal-icon"><i class="fa-solid fa-users"></i></div>
          <div class="legal-text"><h4>Code of Conduct</h4><p>Community standards and expectations</p></div>
          <div class="legal-arrow"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
        <div class="legal-row" onclick="openLegal('privacy')">
          <div class="legal-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <div class="legal-text"><h4>Privacy Policy</h4><p>How we collect, use, and protect your data</p></div>
          <div class="legal-arrow"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         DANGER ZONE
    ============================================================ -->
    <div class="settings-section" id="sec-danger">
      <div class="section-header">
        <h1>Danger Zone</h1>
        <p>These actions are permanent and irreversible. Please proceed with caution.</p>
      </div>

      <div class="card card-danger">
        <div class="danger-row">
          <div>
            <h4>Deactivate account</h4>
            <p>Temporarily hide your account. You can reactivate by logging in again.</p>
          </div>
          <button class="btn btn-sm btn-danger"
            onclick="openConfirm('Deactivate account?','Your account will be hidden. You can reactivate anytime by logging back in.',()=>alert('Account deactivated.'))">
            Deactivate
          </button>
        </div>
        <div class="danger-row">
          <div>
            <h4>Delete rental history</h4>
            <p>Permanently remove all your past rental records. This cannot be undone.</p>
          </div>
          <button class="btn btn-sm btn-danger"
            onclick="openConfirm('Delete rental history?','All your past rental records will be permanently removed. This cannot be undone.',()=>alert('Rental history deleted.'))">
            Delete history
          </button>
        </div>
        <div class="danger-row">
          <div>
            <h4>Delete account</h4>
            <p>Permanently delete your account and all associated data. This is irreversible.</p>
          </div>
          <button class="btn btn-sm btn-danger"
            onclick="openConfirm('Delete your account?','Your account and all data will be permanently deleted and cannot be recovered.',()=>window.location.href='<?= $logout_url ?>')">
            Delete account
          </button>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- =====================================================================
     LEGAL MODALS  (generated from PHP array — easy to update)
===================================================================== -->
<?php
$legal_docs = [
  'terms' => [
    'title'   => 'Terms &amp; Conditions',
    'icon'    => 'fa-file-contract',
    'heading' => $is_landlord ? 'Landlord Terms and Conditions' : 'Tenant Terms and Conditions',
    'intro'   => $is_landlord
      ? 'Welcome to <strong>Map Aware Home</strong>. As a landlord, you agree to the following terms and responsibilities.'
      : 'Welcome to <strong>Map Aware Home</strong>. As a tenant, you agree to the following terms and responsibilities.',
    'sections' => [
      ['1. Account Information',       'You must provide accurate personal information including your full name, contact number, and other details required for registration. Your personal data is handled according to applicable privacy laws and is not shared without your consent.'],
      ['2. Respect and Truthfulness',  'You must maintain respectful interactions with all other users. False profiles, misleading information, or offensive messages are prohibited and will result in immediate account suspension.'],
      ['3. Communication Policy',      'Use the real-time chat feature responsibly for property-related communication only. Avoid sharing unnecessary personal or financial information through the platform messaging system.'],
      ['4. Proximity Mapping &amp; Safety', 'The proximity mapping feature is provided for informational and safety purposes only. It is used to help users verify property locations and assess the surrounding area.'],
      ['5. Payment Reminders',         'Automatic rent payment reminders are a courtesy feature. Map Aware Home does not process financial transactions and is not responsible for payment disputes between parties.'],
      ['6. System Usage',              'You must not attempt unauthorized access to any part of the platform, modify system data, introduce malicious code, or take any action that disrupts platform operation.'],
      ['7. Updates to Terms',          'We may update these terms at any time without prior notice. Continued use of the platform after any update constitutes agreement to the latest version of the terms.'],
    ],
  ],
  'rental' => [
    'title'   => 'Rules Related to Renting',
    'icon'    => 'fa-house-circle-check',
    'heading' => 'Rules Related to Renting',
    'intro'   => '<strong>Map Aware Home</strong> is committed to ensuring fair and transparent rental practices for all users.',
    'sections' => [
      ['Property Listings &amp; Accuracy', 'All property information including location, amenities, price, and safety features must be accurate. Photos must be recent and genuinely represent the current condition of the property. Proximity mapping coordinates must be correct.'],
      ['Rental Agreements',             'All rental agreements must comply with local, regional, and national housing laws. Key terms including the payment schedule, security deposit, and lease duration must be clearly stated before proceeding.'],
      ['Payment Rules',                 'Rent payments are made directly between the landlord and tenant. Map Aware Home does not process, hold, or facilitate any financial transactions between users.'],
      ['Property Maintenance',          'Landlords are obligated to maintain properties in a safe and habitable condition at all times. Tenants are responsible for promptly reporting maintenance issues through the appropriate channel.'],
      ['Tenant Rights',                 'All tenants have the right to safe and habitable housing. Discrimination against tenants based on any protected class — including race, religion, gender, or disability — is strictly prohibited.'],
      ['Landlord Rights',               'Landlords may screen prospective tenants according to legal guidelines. Landlords may set reasonable house rules provided those rules comply fully with applicable local laws.'],
    ],
  ],
  'contract' => [
    'title'   => 'Legal Contract Guidelines',
    'icon'    => 'fa-file-signature',
    'heading' => 'Legal Contract Guidelines',
    'intro'   => 'Important legal information for all <strong>Map Aware Home</strong> users regarding rental contracts.',
    'sections' => [
      ['Platform Role',                  'Map Aware Home is a property management and communication platform only. We do not create, validate, or enforce any rental contracts. Users are strongly advised to consult qualified legal professionals for contract drafting and review.'],
      ['Recommended Contract Elements',  'A legally sound rental contract should include: full legal names and contact information of all parties; the complete property address with proximity mapping coordinates; the lease term, monthly rent amount, and payment due dates; security deposit amount and conditions for its return; a clear outline of maintenance and repair responsibilities; and termination and eviction procedures as required by local law.'],
      ['Legal Compliance',               'All rental agreements entered into by users must comply with local, provincial, and national housing laws. Map Aware Home strongly recommends consulting a licensed attorney before signing any rental contract.'],
      ['Liability Disclaimer',           'Map Aware Home shall not be liable for any contract breaches, property damage, financial losses, or personal disputes arising from rental agreements made through the platform. Users assume all legal and financial risks associated with their rental agreements.'],
    ],
  ],
  'conduct' => [
    'title'   => 'Code of Conduct',
    'icon'    => 'fa-users',
    'heading' => 'Code of Conduct',
    'intro'   => '<strong>Map Aware Home</strong> Community Standards — effective for all registered users.',
    'sections' => [
      ['Respectful Communication',   'All users must communicate with each other in a respectful and professional manner at all times. Harassment, threats, hate speech, or any discriminatory language is strictly prohibited on this platform. The messaging system is for property-related communication only.'],
      ['Honesty and Transparency',   'Users must provide truthful and accurate information about themselves and their properties. Submitting false reviews, misleading listings, or deceptive profile information will result in immediate account suspension.'],
      ['Privacy and Data Protection','All users must respect the privacy of others. Do not share any other user\'s personal information without their explicit consent. Location data obtained through the platform must only be used for its intended safety and navigation purposes.'],
      ['Prohibited Activities',      'The following are strictly prohibited: creating fake accounts or impersonating other users; listing properties you do not own or are not authorized to list; using the platform to facilitate or engage in any illegal activities; and attempting to bypass, exploit, or circumvent any platform security measure.'],
      ['Consequences for Violations','First offense: formal warning and temporary feature restrictions. Second offense: account suspension for 30 days. Severe, criminal, or repeated violations: permanent and irrevocable account termination.'],
      ['Reporting Violations',       'All violations can be reported through the platform\'s Support section. Every report is reviewed within 48 hours. The identity of users who submit reports is kept strictly confidential throughout the review process.'],
    ],
  ],
  'privacy' => [
    'title'   => 'Privacy Policy',
    'icon'    => 'fa-shield-halved',
    'heading' => 'Privacy Policy',
    'intro'   => '<strong>Map Aware Home</strong> is committed to protecting your personal information in accordance with applicable data privacy laws.',
    'sections' => [
      ['Data We Collect',    'We collect the personal information you provide during registration, including your name, contact details, and rental preferences. We also collect usage data such as pages visited, features used, and — when you grant permission — your approximate device location.'],
      ['How We Use Your Data','Your data is used exclusively to: provide and continuously improve platform features; facilitate communication between tenants and landlords; send relevant in-app and email notifications; and ensure the security and integrity of the platform.'],
      ['Data Sharing',       'We do not sell your personal data to any third party under any circumstances. We share data only when it is necessary to deliver the service (for example, sharing your name with a landlord when you submit a rental application) or with trusted service providers who assist in operating the platform under strict confidentiality agreements.'],
      ['Data Retention',     'Your account data is retained for as long as your account remains active. You may request complete deletion of your data at any time by using the Danger Zone settings on this page or by contacting our support team directly.'],
      ['Your Rights',        'You have the right to access, correct, or delete your personal data at any time. You may also withdraw consent for any specific data processing activity. To exercise these rights, please contact our support team through the Support page.'],
      ['Security',           'Map Aware Home implements industry-standard security measures, including encryption and access controls, to protect your data from unauthorized access. However, no online platform is completely immune to all security risks. We recommend using a strong, unique password and enabling two-factor authentication on your account.'],
    ],
  ],
];

foreach ($legal_docs as $key => $doc): ?>
<div class="legal-modal-overlay" id="modal-<?= $key ?>">
  <div class="legal-modal">
    <div class="legal-modal-header">
      <h3>
        <i class="fa-solid <?= $doc['icon'] ?>" style="color:var(--brand);margin-right:8px"></i>
        <?= $doc['title'] ?>
      </h3>
      <button class="legal-modal-close" onclick="closeLegal('<?= $key ?>')">
        <i class="fa-solid fa-times"></i>
      </button>
    </div>
    <div class="legal-modal-body">
      <h1><?= $doc['heading'] ?></h1>
      <p><?= $doc['intro'] ?></p>
      <?php foreach ($doc['sections'] as [$heading, $text]): ?>
        <h2><?= $heading ?></h2>
        <p><?= $text ?></p>
      <?php endforeach; ?>
      <p style="margin-top:1.5rem"><strong>Last updated:</strong> December 2025</p>
    </div>
    <div class="legal-modal-footer">
      <button class="btn btn-primary" onclick="closeLegal('<?= $key ?>')">
        <i class="fa-solid fa-check"></i> I understand
      </button>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- =====================================================================
     CONFIRM DIALOG
===================================================================== -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <h3 id="confirmTitle">Are you sure?</h3>
    <p id="confirmText">This action cannot be undone.</p>
    <div class="confirm-actions">
      <button class="btn" onclick="closeConfirm()">Cancel</button>
      <button class="btn btn-danger" id="confirmOkBtn">Yes, proceed</button>
    </div>
  </div>
</div>

<!-- =====================================================================
     SESSION / NOTIFICATION INIT
===================================================================== -->
<?php if ($is_tenant): ?>
<script>window.currentUser = { id: <?= (int)$_SESSION['tenant_id'] ?>, type: 'tenant' };</script>
<script src="../js/global-notification-init.js"></script>
<?php elseif ($is_landlord): ?>
<script>window.currentUser = { id: <?= (int)$_SESSION['landlord_id'] ?>, type: 'landlord' };</script>
<script src="../js/global-notification-init.js"></script>
<?php endif; ?>

<!-- =====================================================================
     JAVASCRIPT
===================================================================== -->
<script>
/* ------------------------------------------------------------------
   MOBILE NAV (slide-in panel)
------------------------------------------------------------------ */
const navMenuBtn = document.getElementById('navmenu');
const navLinks   = document.querySelector('.nav-links');
const navOverlay = document.getElementById('navOverlay');
const sidebar    = document.getElementById('settingsSidebar');

function openMobileNav()  { navLinks.classList.add('active');    navOverlay.classList.add('active');    document.body.style.overflow='hidden'; }
function closeMobileNav() { navLinks.classList.remove('active'); navOverlay.classList.remove('active'); document.body.style.overflow=''; }

function openMobileSidebar()  { sidebar.classList.add('open');    navOverlay.classList.add('active');    document.body.style.overflow='hidden'; }
function closeMobileSidebar() { sidebar.classList.remove('open'); navOverlay.classList.remove('active'); document.body.style.overflow=''; }

navMenuBtn.addEventListener('click', () => navLinks.classList.contains('active') ? closeMobileNav() : openMobileNav());
document.getElementById('sidebarToggle').addEventListener('click', () => sidebar.classList.contains('open') ? closeMobileSidebar() : openMobileSidebar());

navOverlay.addEventListener('click', () => { closeMobileNav(); closeMobileSidebar(); });
navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMobileNav));
window.addEventListener('resize', () => { if (window.innerWidth > 720) { closeMobileNav(); closeMobileSidebar(); } });

/* ------------------------------------------------------------------
   SETTINGS SIDEBAR NAVIGATION
------------------------------------------------------------------ */
const sidebarItems = document.querySelectorAll('.sidebar-item[data-section]');
const sections     = document.querySelectorAll('.settings-section');

sidebarItems.forEach(item => {
  item.addEventListener('click', () => {
    sidebarItems.forEach(i => i.classList.remove('active'));
    item.classList.add('active');

    sections.forEach(s => s.classList.remove('visible'));
    const target = document.getElementById('sec-' + item.dataset.section);
    if (target) target.classList.add('visible');

    closeMobileSidebar();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
});

/* ------------------------------------------------------------------
   NOTIFICATION CLEAR
------------------------------------------------------------------ */
document.getElementById('clearNotifications').addEventListener('click', () => {
  document.getElementById('notificationList').innerHTML = '<div class="bell-empty">No notifications</div>';
  const badge = document.getElementById('bellBadge');
  if (badge) badge.style.display = 'none';
});

/* ------------------------------------------------------------------
   PROFILE FORM
------------------------------------------------------------------ */
document.getElementById('profileForm').addEventListener('submit', async e => {
  e.preventDefault();
  const alertEl = document.getElementById('profileAlert');
  const btn     = e.target.querySelector('[type=submit]');
  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

  try {
    const res  = await fetch('../API/update_profile.php', { method: 'POST', body: new FormData(e.target) });
    const data = await res.json();
    showAlert(alertEl, data.success ? 'success' : 'error',
      data.success ? 'Profile updated successfully.' : (data.error || 'An error occurred.'));
  } catch {
    showAlert(alertEl, 'error', 'Could not connect to server. Please try again.');
  } finally {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-regular fa-floppy-disk"></i> Save changes';
    setTimeout(() => hideAlert(alertEl), 5000);
  }
});

/* ------------------------------------------------------------------
   ADDRESS FORM
------------------------------------------------------------------ */
document.getElementById('addressForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = e.target.querySelector('[type=submit]');
  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
  try {
    await fetch('../API/update_address.php', { method: 'POST', body: new FormData(e.target) });
  } catch {}
  btn.disabled  = false;
  btn.innerHTML = '<i class="fa-regular fa-floppy-disk"></i> Save address';
});

/* ------------------------------------------------------------------
   PASSWORD STRENGTH METER
------------------------------------------------------------------ */
document.getElementById('newPassword').addEventListener('input', function () {
  const val    = this.value;
  const fill   = document.getElementById('strengthFill');
  const label  = document.getElementById('strengthLabel');
  let score = 0;
  if (val.length >= 8)           score++;
  if (/[A-Z]/.test(val))         score++;
  if (/[0-9]/.test(val))         score++;
  if (/[^A-Za-z0-9]/.test(val))  score++;
  const levels = [
    { pct:'0%',   color:'transparent', text:'' },
    { pct:'25%',  color:'#e74c3c',     text:'Weak' },
    { pct:'50%',  color:'#f39c12',     text:'Fair' },
    { pct:'75%',  color:'#3498db',     text:'Good' },
    { pct:'100%', color:'#27ae60',     text:'Strong' },
  ];
  fill.style.width      = levels[score].pct;
  fill.style.background = levels[score].color;
  label.textContent     = score > 0 ? 'Strength: ' + levels[score].text : '';
});

/* ------------------------------------------------------------------
   PASSWORD FORM SUBMIT
------------------------------------------------------------------ */
document.getElementById('changePasswordForm').addEventListener('submit', async e => {
  e.preventDefault();
  const alertEl    = document.getElementById('passwordAlert');
  const btn        = document.getElementById('submitPasswordBtn');
  const newPwd     = document.getElementById('newPassword').value;
  const confirmPwd = document.getElementById('confirmPassword').value;

  if (newPwd !== confirmPwd)  { showAlert(alertEl, 'error', 'Passwords do not match.'); return; }
  if (newPwd.length < 8)      { showAlert(alertEl, 'error', 'Password must be at least 8 characters.'); return; }

  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';

  try {
    const res  = await fetch('../API/change_password.php', { method: 'POST', body: new FormData(e.target) });
    const data = await res.json();
    if (data.success) {
      showAlert(alertEl, 'success', 'Password changed successfully.');
      e.target.reset();
      document.getElementById('strengthFill').style.width = '0';
      document.getElementById('strengthLabel').textContent = '';
    } else {
      showAlert(alertEl, 'error', data.error || 'An error occurred. Please try again.');
    }
  } catch {
    showAlert(alertEl, 'error', 'Could not connect to server. Please try again.');
  } finally {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-key"></i> Update password';
    setTimeout(() => hideAlert(alertEl), 5000);
  }
});

/* ------------------------------------------------------------------
   AVATAR PREVIEW
------------------------------------------------------------------ */
document.getElementById('avatarInput').addEventListener('change', function () {
  const file = this.files[0];
  if (!file || !file.type.startsWith('image/')) return;
  const reader = new FileReader();
  reader.onload = e => {
    const circle = document.getElementById('avatarCircle');
    circle.style.backgroundImage   = `url(${e.target.result})`;
    circle.style.backgroundSize    = 'cover';
    circle.style.backgroundPosition = 'center';
    circle.textContent = '';
  };
  reader.readAsDataURL(file);
});

document.getElementById('removeAvatarBtn').addEventListener('click', () => {
  const circle = document.getElementById('avatarCircle');
  circle.style.backgroundImage = 'none';
  circle.textContent = '<?= strtoupper(substr($_SESSION['username'], 0, 2)) ?>';
  document.getElementById('avatarInput').value = '';
});

/* ------------------------------------------------------------------
   LEGAL MODALS
------------------------------------------------------------------ */
function openLegal(key)  { document.getElementById('modal-'+key).classList.add('active');    document.body.style.overflow='hidden'; }
function closeLegal(key) { document.getElementById('modal-'+key).classList.remove('active'); document.body.style.overflow=''; }

document.querySelectorAll('.legal-modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeLegal(overlay.id.replace('modal-',''));
  });
});

/* ------------------------------------------------------------------
   CONFIRM DIALOG
------------------------------------------------------------------ */
let _confirmCb = null;

function openConfirm(title, text, cb) {
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmText').textContent  = text;
  _confirmCb = cb;
  document.getElementById('confirmOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeConfirm() {
  document.getElementById('confirmOverlay').classList.remove('active');
  document.body.style.overflow = '';
  _confirmCb = null;
}

document.getElementById('confirmOkBtn').addEventListener('click', () => {
  closeConfirm();
  if (_confirmCb) _confirmCb();
});
document.getElementById('confirmOverlay').addEventListener('click', e => {
  if (e.target === document.getElementById('confirmOverlay')) closeConfirm();
});

function confirmRevoke(btn, deviceName) {
  openConfirm('Revoke session?', '"' + deviceName + '" will be signed out immediately.', () => {
    btn.closest('.session-row').style.opacity = '0.4';
    btn.textContent = 'Revoked';
    btn.disabled    = true;
  });
}

/* ------------------------------------------------------------------
   ALERT HELPERS
------------------------------------------------------------------ */
function showAlert(el, type, msg) {
  el.className = 'alert show alert-' + type;
  const icon   = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
  el.innerHTML = `<i class="fa-solid ${icon}"></i> ${msg}`;
}
function hideAlert(el) { el.className = 'alert'; el.innerHTML = ''; }

/* ------------------------------------------------------------------
   KEYBOARD SHORTCUTS
------------------------------------------------------------------ */
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  document.querySelectorAll('.legal-modal-overlay.active').forEach(m => closeLegal(m.id.replace('modal-','')));
  closeConfirm();
});
</script>

<script src="../js/chat-notifications.js?v=<?= time() ?>"></script>
</body>
</html>