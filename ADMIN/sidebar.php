<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tahanan</title>
    
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Inter:wght@400;500&display=swap');

        * {
          margin: 0;
          padding: 0;
          font-family: 'Montserrat', sans-serif;
          box-sizing: border-box;
        }

        :root {
          --body-color: #f8f9fa;
          --sidebar-color: #1a1d29;
          --sidebar-hover: #252938;
          --primary-color: #4a90e2;
          --primary-hover: #357abd;
          --text-color: #e4e6eb;
          --text-muted: #8b92a7;
          --border-color: #2d3142;
          --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
          min-height: 100vh;
          background: var(--body-color);
          overflow-x: hidden;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
          position: fixed;
          top: 0;
          left: 0;
          height: 100vh;
          width: 260px;
          background: var(--sidebar-color);
          padding: 0;
          transition: var(--transition);
          z-index: 1000;
          box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
          width: 75px;
        }

        /* ========== HEADER ========== */
        .sidebar header {
          padding: 20px 16px;
          border-bottom: 1px solid var(--border-color);
          background: linear-gradient(135deg, #1e2230 0%, #1a1d29 100%);
        }

        .sidebar .image-text {
          display: flex;
          align-items: center;
          gap: 12px;
          transition: var(--transition);
        }

        .sidebar .image-text img {
          width: 42px;
          height: 42px;
          border-radius: 10px;
          object-fit: cover;
          border: 2px solid var(--primary-color);
          transition: var(--transition);
        }

        .sidebar.collapsed .image-text img {
          width: 38px;
          height: 38px;
        }

        .sidebar .header-text {
          display: flex;
          flex-direction: column;
          gap: 2px;
          opacity: 1;
          transition: var(--transition);
        }

        .sidebar.collapsed .header-text {
          opacity: 0;
          width: 0;
          overflow: hidden;
        }

        .header-text .name {
          font-size: 16px;
          font-weight: 600;
          color: var(--text-color);
          white-space: nowrap;
          cursor: pointer;
          transition: color 0.2s;
        }

        .header-text .name:hover {
          color: var(--primary-color);
        }

        .header-text .role {
          font-size: 12px;
          color: var(--text-muted);
          font-weight: 400;
        }

        /* ========== TOGGLE BUTTON ========== */
        .sidebar header .toggle {
          position: absolute;
          top: 26px;
          right: -14px;
          height: 28px;
          width: 28px;
          background: var(--primary-color);
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 50%;
          color: white;
          font-size: 16px;
          cursor: pointer;
          transition: var(--transition);
          box-shadow: 0 2px 8px rgba(74, 144, 226, 0.4);
        }

        .sidebar header .toggle:hover {
          background: var(--primary-hover);
          transform: scale(1.1);
        }

        .sidebar.collapsed header .toggle {
          transform: rotate(180deg);
        }

        /* ========== MENU BAR ========== */
        .sidebar .menu-bar {
          height: calc(100% - 82px);
          display: flex;
          flex-direction: column;
          padding: 16px 0;
          overflow-y: auto;
          overflow-x: hidden;
        }

        .sidebar .menu-bar::-webkit-scrollbar {
          width: 4px;
        }

        .sidebar .menu-bar::-webkit-scrollbar-thumb {
          background: var(--border-color);
          border-radius: 10px;
        }

        /* ========== MENU ITEMS ========== */
        .sidebar .menu {
          padding: 0 12px;
        }

        .sidebar .menu-links {
          padding: 0;
          margin: 0;
        }

        .sidebar li {
          list-style: none;
          margin: 4px 0;
        }

        .sidebar li a {
          display: flex;
          align-items: center;
          height: 48px;
          padding: 0 14px;
          text-decoration: none;
          border-radius: 10px;
          transition: var(--transition);
          position: relative;
          overflow: hidden;
        }

        .sidebar li a::before {
          content: '';
          position: absolute;
          left: 0;
          top: 0;
          height: 100%;
          width: 3px;
          background: var(--primary-color);
          transform: scaleY(0);
          transition: transform 0.2s;
        }

        .sidebar li a:hover {
          background: var(--sidebar-hover);
        }

        .sidebar li a:hover::before {
          transform: scaleY(1);
        }

        .sidebar li a.active {
          background: linear-gradient(90deg, rgba(74, 144, 226, 0.15) 0%, rgba(74, 144, 226, 0.05) 100%);
          color: var(--primary-color);
        }

        .sidebar li a.active::before {
          transform: scaleY(1);
        }

        .sidebar li .icon {
          min-width: 45px;
          display: flex;
          align-items: center;
          justify-content: flex-start;
          font-size: 20px;
          color: var(--text-muted);
          transition: var(--transition);
        }

        .sidebar.collapsed li .icon {
          justify-content: center;
          min-width: 100%;
        }

        .sidebar li a:hover .icon,
        .sidebar li a.active .icon {
          color: var(--primary-color);
        }

        .sidebar .text {
          font-size: 14px;
          font-weight: 500;
          color: var(--text-color);
          white-space: nowrap;
          opacity: 1;
          transition: var(--transition);
        }

        .sidebar.collapsed .text {
          opacity: 0;
          width: 0;
        }

        /* ========== MENU SECTIONS ========== */
        .menu-section-title {
          font-size: 11px;
          font-weight: 600;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 1px;
          padding: 20px 14px 8px;
          transition: var(--transition);
        }

        .sidebar.collapsed .menu-section-title {
          opacity: 0;
          height: 0;
          padding: 0;
          overflow: hidden;
        }

        /* ========== BOTTOM MENU ========== */
        .sidebar .bottom-content {
          margin-top: auto;
          padding: 16px 12px;
          border-top: 1px solid var(--border-color);
        }

        /* ========== USER INFO ========== */
        .user-info {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 12px 14px;
          background: var(--sidebar-hover);
          border-radius: 10px;
          transition: var(--transition);
          cursor: pointer;
        }

        .user-info:hover {
          background: linear-gradient(90deg, rgba(74, 144, 226, 0.15) 0%, rgba(74, 144, 226, 0.05) 100%);
        }

        .user-info .user-avatar {
          width: 36px;
          height: 36px;
          border-radius: 50%;
          object-fit: cover;
          border: 2px solid var(--primary-color);
        }

        .user-info .user-details {
          flex: 1;
          opacity: 1;
          transition: var(--transition);
        }

        .sidebar.collapsed .user-info .user-details {
          opacity: 0;
          width: 0;
          overflow: hidden;
        }

        .user-info .user-name {
          font-size: 13px;
          font-weight: 600;
          color: var(--text-color);
          display: block;
          line-height: 1.3;
        }

        .user-info .user-status {
          font-size: 11px;
          color: var(--text-muted);
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
          margin-left: 260px;
          padding: 24px;
          transition: var(--transition);
          min-height: 100vh;
        }

        .sidebar.collapsed ~ .main-content {
          margin-left: 75px;
        }

        /* ========== TOOLTIP ========== */
        .sidebar.collapsed li a {
          position: relative;
        }

        .sidebar.collapsed li a:hover::after {
          content: attr(data-tooltip);
          position: absolute;
          left: 70px;
          top: 50%;
          transform: translateY(-50%);
          background: var(--sidebar-color);
          color: var(--text-color);
          padding: 8px 12px;
          border-radius: 6px;
          font-size: 13px;
          white-space: nowrap;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
          z-index: 1001;
          animation: fadeIn 0.2s;
        }

        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }

        /* ========== BADGE ========== */
        .sidebar .badge {
          position: absolute;
          right: 14px;
          background: #ef4444;
          color: white;
          font-size: 10px;
          font-weight: 600;
          padding: 2px 6px;
          border-radius: 10px;
          min-width: 18px;
          text-align: center;
          transition: var(--transition);
        }

        .sidebar.collapsed .badge {
          right: 8px;
          top: 8px;
        }

        /* ========== ANIMATIONS ========== */
        @keyframes slideIn {
          from {
            opacity: 0;
            transform: translateX(-10px);
          }
          to {
            opacity: 1;
            transform: translateX(0);
          }
        }

        .sidebar li {
          animation: slideIn 0.3s ease forwards;
        }

        .sidebar li:nth-child(1) { animation-delay: 0.05s; }
        .sidebar li:nth-child(2) { animation-delay: 0.1s; }
        .sidebar li:nth-child(3) { animation-delay: 0.15s; }
        .sidebar li:nth-child(4) { animation-delay: 0.2s; }
        .sidebar li:nth-child(5) { animation-delay: 0.25s; }
        .sidebar li:nth-child(6) { animation-delay: 0.3s; }
        .sidebar li:nth-child(7) { animation-delay: 0.35s; }
        .sidebar li:nth-child(8) { animation-delay: 0.4s; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
          .sidebar {
            transform: translateX(-100%);
          }

          .sidebar.active {
            transform: translateX(0);
          }

          .main-content {
            margin-left: 0;
          }

          .sidebar.collapsed ~ .main-content {
            margin-left: 0;
          }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <!-- Header -->
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="https://via.placeholder.com/42" alt="Tahanan Logo">
                </span>
                <div class="header-text">
                    <span class="name">Tahanan</span>
                    <span class="role">Admin Panel</span>
                </div>
            </div>
            <i class='bx bx-chevron-right toggle'></i>
        </header>

        <!-- Menu Bar -->
        <div class="menu-bar">
            <div class="menu">
                
                <!-- Main Section -->
                <div class="menu-section-title">Main</div>
                <ul class="menu-links">
                    <li>
                        <a href="dashboard.php" class="active" data-tooltip="Home">
                            <i class='bx bx-home icon'></i>
                            <span class="text">Home</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="accounts.php" data-tooltip="Accounts">
                            <i class='bx bx-user icon'></i>
                            <span class="text">Accounts</span>
                        </a>
                    </li>
                </ul>

                <!-- Management Section -->
                <div class="menu-section-title">Management</div>
                <ul class="menu-links">
                    <li>
                        <a href="reports.php" data-tooltip="Reports">
                            <i class='bx bx-bar-chart-alt-2 icon'></i>
                            <span class="text">Reports</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="listing.php" data-tooltip="Listing">
                            <i class='bx bx-building-house icon'></i>
                            <span class="text">Listing</span>
                        </a>
                    </li>
                    
                    <li>
                      <a href="verify-properties.php" data-tooltip="Verify Properties">
                          <i class='bx bx-shield-alt-2 icon'></i>
                          <span class="text">Verify Properties</span>
                          <span class="badge">3</span>
                      </a>
                  </li>
                  <li>
                      <a href="verify-landlord.php" data-tooltip="Verify Landlord">
                          <i class='bx bx-shield-check icon'></i>
                          <span class="text">Verify Landlord</span>
                          <span class="badge">3</span>
                      </a>
                  </li>
                </ul>
            </div>

            <!-- Bottom Content -->
            <div class="bottom-content">
                <!-- User Info -->
                <div class="user-info">
                    <img src="https://via.placeholder.com/36" alt="Admin" class="user-avatar">
                    <div class="user-details">
                        <span class="user-name">Admin User</span>
                        <span class="user-status">Online</span>
                    </div>
                </div>

                <!-- Logout -->
                <ul class="menu-links">
                    <li>
                        <a href="logout.php" data-tooltip="Logout">
                            <i class='bx bx-log-out icon'></i>
                            <span class="text">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <h1>👋 Welcome back, Tahanan!</h1>
        <p>Here's what's happening with your platform today • Thursday, March 5, 2026</p>
        
        <!-- Your dashboard content goes here -->
    </div>

    <!-- JAVASCRIPT -->
    <script>
        // Sidebar toggle with localStorage
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.toggle');

        // Restore sidebar state
        if (localStorage.getItem('sidebarState') === 'collapsed') {
            sidebar.classList.add('collapsed');
        }

        // Toggle sidebar
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem(
                'sidebarState',
                sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded'
            );
        });

        // Set active menu item based on current page
        const currentPage = window.location.pathname.split('/').pop();
        const menuLinks = document.querySelectorAll('.sidebar a');

        menuLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Mobile: Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !e.target.closest('.mobile-menu-toggle')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>

</body>
</html>