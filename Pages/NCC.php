<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCC Dashboard - ReqZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Inter', Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #373047ff 100%);
            min-height: 100vh;

        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Animated Background Elements */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }

        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        .floating-shape:nth-child(1) {
            width: 100px;
            height: 100px;
            left: 10%;
            animation-duration: 15s;
        }

        .floating-shape:nth-child(2) {
            width: 150px;
            height: 150px;
            left: 70%;
            animation-duration: 20s;
            animation-delay: -5s;
        }

        .floating-shape:nth-child(3) {
            width: 80px;
            height: 80px;
            left: 40%;
            animation-duration: 18s;
            animation-delay: -10s;
        }

        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 50%, #667eea 100%);
            width: 280px;
            min-width: 280px;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 20px rgba(0,0,0,0.2);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255,255,255,0.1);
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .logo-container {
            position: relative;
            z-index: 2;
        }

        .logo {
            width: 180px;
            margin-bottom: 15px;
            filter: brightness(0) invert(1) drop-shadow(0 0 10px rgba(255,255,255,0.3));
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .user-info {
            color: white;
            margin-top: 15px;
        }

        .user-info h3 {
            font-size: 1.2rem;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .user-info .role-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-top: 8px;
            display: inline-block;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .sidebar-nav {
            flex: 1;
            padding: 30px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 18px 30px;
            color: rgba(255,255,255,0.8);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            font-size: 1.1rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            transition: width 0.3s ease;
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            width: 100%;
        }

        .nav-item:hover,
        .nav-item.active {
            color: white;
            transform: translateX(10px) scale(1.02);
            background: rgba(255,255,255,0.1);
            box-shadow: inset 5px 0 0 #ffeb3b;
        }

        .nav-item i {
            margin-right: 15px;
            font-size: 1.4rem;
            width: 25px;
            transition: transform 0.3s ease;
        }

        .nav-item:hover i {
            transform: scale(1.2) rotate(10deg);
        }

        .nav-item .notification-dot {
            position: absolute;
            right: 20px;
            width: 10px;
            height: 10px;
            background: #ff4757;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px 0 0 20px;
            margin: 20px 20px 20px 0;
            overflow: hidden;
            position: relative;
        }

        .content-section {
            display: none;
            padding: 40px;
            height: calc(100vh - 40px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.3) transparent;
        }

        .content-section::-webkit-scrollbar {
            width: 6px;
        }

        .content-section::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
        }

        .content-section::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .content-section.active {
            display: block;
            animation: slideInRight 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        @keyframes slideInRight {
            0% { transform: translateX(50px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #ffeb3b, #ff9800);
            border-radius: 2px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .notification-bell {
            position: relative;
            color: #ffeb3b;
            font-size: 1.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification-bell:hover {
            transform: rotate(15deg) scale(1.1);
            color: #ffc107;
        }

        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 100%);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            border-color: rgba(255,255,255,0.4);
        }

        .stat-card-content {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            z-index: 2;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stat-icon.submissions {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        }

        .stat-icon.responses {
            background: linear-gradient(135deg, #4ecdc4, #44bd87);
        }

        .stat-icon.memos {
            background: linear-gradient(135deg, #a55eea, #8b68ff);
        }

        .stat-icon.pending {
            background: linear-gradient(135deg, #ffa726, #ff7043);
        }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 5px;
            counter-reset: num;
            animation: countUp 1s ease-out;
        }

        .stat-info p {
            color: rgba(255,255,255,0.9);
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @keyframes countUp {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        /* Table Styles */
        .data-table-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .table-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            padding: 25px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .table-header h2 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: rgba(36, 34, 56, 0.3)
            color: white;
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .data-table td {
            padding: 18px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .data-table tr:hover td {
            background: rgba(255,255,255,0.1);
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .status-badge:hover::before {
            left: 100%;
        }

        .status-badge.new {
            background: linear-gradient(135deg, #4ecdc4, #44bd87);
            color: white;
        }

        .status-badge.reviewed {
            background: linear-gradient(135deg, #ffa726, #ff7043);
            color: white;
        }

        .status-badge.responded {
            background: linear-gradient(135deg, #a55eea, #8b68ff);
            color: white;
        }

        /* Form Styles */
        .form-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: white;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.2);
            background: rgba(0, 0, 0, 0.1);
            color:  rgba(8, 15, 71, 1);
            font-size: 1rem;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ffeb3b;
            background: rgba(255,255,255,0.15);
            box-shadow: 0 0 0 4px rgba(255, 235, 59, 0.2);
            transform: translateY(-2px);
        }

        .submit-btn {
            background: linear-gradient(135deg, #4ecdc4, #44bd87);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 25px rgba(78, 205, 196, 0.3);
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(78, 205, 196, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px) scale(1.02);
        }

        /* Mobile Responsive */
        .mobile-nav {
            display: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .mobile-menu-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .mobile-menu-btn:hover {
            transform: scale(1.1) rotate(90deg);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                transform: translateX(-100%);
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1000;
                height: 100vh;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .mobile-nav {
                display: flex;
            }

            .main-content {
                margin: 0;
                border-radius: 0;
                height: calc(100vh - 80px);
                margin-top: 80px;
            }

            .content-section {
                padding: 20px;
                height: calc(100vh - 140px);
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .section-title {
                font-size: 2rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            cursor: help;
        }

        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .tooltip:hover::after {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <div class="dashboard-container">
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <h3>NCC Dashboard</h3>
            <button class="mobile-menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <svg class="logo" viewBox="0 0 180 50" xmlns="http://www.w3.org/2000/svg">
                        <text x="90" y="30" font-family="Arial, sans-serif" font-size="24" font-weight="bold" text-anchor="middle" fill="white">ReqZone</text>
                        <text x="90" y="42" font-family="Arial, sans-serif" font-size="8" text-anchor="middle" fill="rgba(255,255,255,0.8)">NCC Portal</text>
                    </svg>
                    <div class="user-info">
                        <h3 id="userName">NCC Officer</h3>
                        <div class="role-badge">Senior Administrator</div>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item active" onclick="showSection('dashboard', this)">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                    <div class="notification-dot" style="display: none;"></div>
                </div>
                <div class="nav-item" onclick="showSection('requests', this)">
                    <i class="fas fa-inbox"></i>
                    <span>Incoming Requests</span>
                    <div class="notification-dot"></div>
                </div>
                <div class="nav-item" onclick="showSection('memo', this)">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Memo</span>
                </div>
                <div class="nav-item" onclick="showSection('archive', this)">
                    <i class="fas fa-archive"></i>
                    <span>Archive</span>
                </div>
                <div class="nav-item" onclick="showSection('analytics', this)">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </div>
                <div class="nav-item" onclick="showSection('profile', this)">
                    <i class="fas fa-user-shield"></i>
                    <span>Profile</span>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Section -->
            <div class="content-section active" id="dashboard">
                <div class="section-header">
                    <h1 class="section-title">Dashboard Overview</h1>
                    <div class="header-actions">
                        <div class="notification-bell tooltip" data-tooltip="3 new notifications" onclick="showNotifications()">
                            <i class="fas fa-bell"></i>
                            <div class="notification-count">3</div>
                        </div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card" onclick="showSection('requests', document.querySelector('.nav-item:nth-child(2)'))">
                        <div class="stat-card-content">
                            <div class="stat-icon submissions">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="submissionsCount">12</h3>
                                <p>New Submissions</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon responses">
                                <i class="fas fa-reply-all"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="responsesCount">8</h3>
                                <p>Responses Sent</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="showSection('memo', document.querySelector('.nav-item:nth-child(3)'))">
                        <div class="stat-card-content">
                            <div class="stat-icon memos">
                                <i class="fas fa-memo-circle-info"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="memosCount">5</h3>
                                <p>Internal Memos</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="pendingCount">4</h3>
                                <p>Pending Actions</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="data-table-container">
                    <div class="table-header">
                        <h2>Recent Activity</h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recentActivity">
                            <tr>
                                <td>Diamond Heirs</td>
                                <td>Q4 Payment Request</td>
                                <td>Payment</td>
                                <td>Dec 15, 2024</td>
                                <td><span class="status-badge new">New</span></td>
                                <td><span class="tooltip" data-tooltip="Requires immediate attention">High</span></td>
                                <td>
                                    <button onclick="viewDocument(1)" class="action-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Facility B</td>
                                <td>IT Support Request</td>
                                <td>Job</td>
                                <td>Dec 14, 2024</td>
                                <td><span class="status-badge reviewed">Reviewed</span></td>
                                <td>Medium</td>
                                <td>
                                    <button onclick="viewDocument(2)" class="action-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Facility C</td>
                                <td>Maintenance Contract</td>
                                <td>Job</td>
                                <td>Dec 13, 2024</td>
                                <td><span class="status-badge responded">Responded</span></td>
                                <td>Low</td>
                                <td>
                                    <button onclick="viewDocument(3)" class="action-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Incoming Requests Section -->
            <div class="content-section" id="requests">
                <div class="section-header">
                    <h1 class="section-title">Incoming Requests</h1>
                    <div class="header-actions">
                        <button class="filter-btn" onclick="toggleFilters()">
                            <i class="fas fa-filter"></i> Filters
                        </button>
                        <button class="refresh-btn" onclick="refreshRequests()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <div class="filters-panel" id="filtersPanel" style="display: none;">
                    <div class="filter-group">
                        <select id="statusFilter" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="new">New</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="responded">Responded</option>
                        </select>
                        <select id="typeFilter" onchange="applyFilters()">
                            <option value="">All Types</option>
                            <option value="payment">Payment</option>
                            <option value="job">Job</option>
                        </select>
                        <input type="date" id="dateFilter" onchange="applyFilters()">
                    </div>
                </div>

                <div class="requests-container" id="requestsContainer">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>

            <!-- Send Memo Section -->
            <div class="content-section" id="memo">
                <div class="section-header">
                    <h1 class="section-title">Send Internal Memo</h1>
                </div>

                <div class="form-container">
                    <form id="memoForm" onsubmit="sendMemo(event)">
                        <div class="form-group">
                            <label for="memoTitle">Memo Title</label>
                            <input type="text" id="memoTitle" name="memo-title" placeholder="Enter memo title..." required>
                        </div>

                        <div class="form-group">
                            <label for="recipient">Recipient</label>
                            <select id="recipient" name="recipient" required>
                                <option value="">Select recipient...</option>
                                <option value="Diamond Heirs">Diamond Heirs Facility</option>
                                <option value="Facility B">Facility B</option>
                                <option value="Internal-CSL">Internal - Corporate Services</option>
                                <option value="Internal-UHCSL">Internal - Unit Head CSL</option>
                                <option value="Internal-ALL">All NCC Staff</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="priority">Priority Level</label>
                            <select id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="memoMessage">Message</label>
                            <textarea id="memoMessage" name="memo-message" rows="8" placeholder="Type your memo or response..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="attachment">Attach Files (optional)</label>
                            <input type="file" id="attachment" name="memo-attachment" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send Memo</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Archive Section -->
            <div class="content-section" id="archive">
                <div class="section-header">
                    <h1 class="section-title">Document Archive</h1>
                    <div class="header-actions">
                        <button class="search-btn" onclick="toggleSearch()">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <button class="export-btn" onclick="exportArchive()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <div class="search-panel" id="searchPanel" style="display: none;">
                    <input type="text" id="searchInput" placeholder="Search documents..." onkeyup="searchArchive()">
                </div>

                <div class="archive-grid" id="archiveGrid">
                    <!-- Archive items will be loaded here -->
                </div>
            </div>

            <!-- Analytics Section -->
            <div class="content-section" id="analytics">
                <div class="section-header">
                    <h1 class="section-title">Analytics Dashboard</h1>
                    <div class="header-actions">
                        <select id="timeRange" onchange="updateCharts()">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 3 months</option>
                        </select>
                    </div>
                </div>

                <div class="analytics-grid">
                    <div class="chart-container">
                        <h3>Request Trends</h3>
                        <canvas id="requestChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3>Response Times</h3>
                        <canvas id="responseChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Profile Section -->
            <div class="content-section" id="profile">
                <div class="section-header">
                    <h1 class="section-title">Profile Settings</h1>
                </div>

                <div class="form-container">
                    <div class="profile-section">
                        <div class="profile-avatar">
                            <div class="avatar-container">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='60' fill='%23667eea'/%3E%3Ccircle cx='60' cy='45' r='20' fill='%23fff'/%3E%3Cpath d='M20 100 Q20 75 60 75 Q100 75 100 100 Z' fill='%23fff'/%3E%3C/svg%3E" 
                                     alt="Profile Picture" id="profileImage">
                                <button class="edit-avatar-btn" onclick="editAvatar()">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>

                        <form id="profileForm" onsubmit="updateProfile(event)">
                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text" id="fullName" value="John Smith" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" value="john.smith@ncc.gov.ng" required>
                            </div>

                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" value="Corporate Services LGZO" readonly>
                            </div>

                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" id="position" value="Senior Administrator" readonly>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" id="newPassword" placeholder="Leave blank to keep current password">
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <input type="password" id="confirmPassword" placeholder="Confirm new password">
                            </div>

                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i>
                                <span>Save Changes</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentUser = {
            name: 'John Smith',
            role: 'Senior Administrator',
            department: 'Corporate Services LGZO'
        };

        let requestsData = [
            {
                id: 1,
                from: 'Diamond Heirs',
                document: 'Q4 Payment Request',
                type: 'Payment',
                date: '2024-12-15',
                status: 'new',
                priority: 'high',
                description: 'Quarterly payment request for facility management services.'
            },
            {
                id: 2,
                from: 'Facility B',
                document: 'IT Support Request',
                type: 'Job',
                date: '2024-12-14',
                status: 'reviewed',
                priority: 'medium',
                description: 'Request for IT infrastructure support and maintenance.'
            },
            {
                id: 3,
                from: 'Facility C',
                document: 'Maintenance Contract',
                type: 'Job',
                date: '2024-12-13',
                status: 'responded',
                priority: 'low',
                description: 'Annual maintenance contract renewal request.'
            }
        ];

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            animateCounters();
            loadRequests();
            loadArchive();
        });

        // Navigation functions
        function showSection(sectionId, navElement) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked nav item
            if (navElement) {
                navElement.classList.add('active');
            }

            // Close mobile sidebar
            document.getElementById('sidebar').classList.remove('open');

            // Load section-specific data
            switch(sectionId) {
                case 'requests':
                    loadRequests();
                    break;
                case 'archive':
                    loadArchive();
                    break;
                case 'analytics':
                    loadAnalytics();
                    break;
            }
        }

        // Mobile sidebar toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Initialize dashboard
        function initializeDashboard() {
            document.getElementById('userName').textContent = currentUser.name;
            updateDateTime();
            setInterval(updateDateTime, 1000);
        }

        // Update date and time
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            // You can add a date/time display element if needed
        }

        // Animate counters
        function animateCounters() {
            const counters = ['submissionsCount', 'responsesCount', 'memosCount', 'pendingCount'];
            const values = [12, 8, 5, 4];

            counters.forEach((id, index) => {
                animateCounter(id, values[index]);
            });
        }

        function animateCounter(elementId, targetValue) {
            const element = document.getElementById(elementId);
            let currentValue = 0;
            const increment = targetValue / 30;
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= targetValue) {
                    element.textContent = targetValue;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(currentValue);
                }
            }, 50);
        }

        // Load requests
        function loadRequests() {
            const container = document.getElementById('requestsContainer');
            
            container.innerHTML = `
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${requestsData.map(request => `
                                <tr>
                                    <td>${request.from}</td>
                                    <td>${request.document}</td>
                                    <td>${request.type}</td>
                                    <td>${formatDate(request.date)}</td>
                                    <td><span class="priority-${request.priority}">${request.priority.toUpperCase()}</span></td>
                                    <td><span class="status-badge ${request.status}">${request.status.toUpperCase()}</span></td>
                                    <td>
                                        <button onclick="viewDocument(${request.id})" class="action-btn view-btn" title="View Document">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="approveRequest(${request.id})" class="action-btn approve-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="rejectRequest(${request.id})" class="action-btn reject-btn" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        // Send memo
        function sendMemo(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitBtn = form.querySelector('.submit-btn');
            const originalContent = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<div class="loading"></div> Sending...';
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                showNotification('Memo sent successfully!', 'success');
                form.reset();
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                
                // Update memo count
                const memoCount = document.getElementById('memosCount');
                memoCount.textContent = parseInt(memoCount.textContent) + 1;
            }, 2000);
        }

        // Load archive
        function loadArchive() {
            const grid = document.getElementById('archiveGrid');
            
            grid.innerHTML = `
                <div class="archive-stats">
                    <div class="archive-stat">
                        <h3>247</h3>
                        <p>Total Documents</p>
                    </div>
                    <div class="archive-stat">
                        <h3>89%</h3>
                        <p>Processing Rate</p>
                    </div>
                    <div class="archive-stat">
                        <h3>2.3 days</h3>
                        <p>Avg Response Time</p>
                    </div>
                </div>
                <div class="archive-message">
                    <i class="fas fa-archive" style="font-size: 3rem; color: rgba(255,255,255,0.3); margin-bottom: 20px;"></i>
                    <h3>Archive Management</h3>
                    <p>Complete document history and processing analytics will be displayed here.</p>
                </div>
            `;
        }

        // Utility functions
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Action functions
        function viewDocument(id) {
            showNotification(`Opening document #${id}...`, 'info');
        }

        function approveRequest(id) {
            if (confirm('Are you sure you want to approve this request?')) {
                showNotification(`Request #${id} approved successfully!`, 'success');
                updateRequestStatus(id, 'approved');
            }
        }

        function rejectRequest(id) {
            if (confirm('Are you sure you want to reject this request?')) {
                showNotification(`Request #${id} rejected.`, 'info');
                updateRequestStatus(id, 'rejected');
            }
        }

        function updateRequestStatus(id, status) {
            const request = requestsData.find(r => r.id === id);
            if (request) {
                request.status = status;
                loadRequests();
            }
        }

        // Filter and search functions
        function toggleFilters() {
            const panel = document.getElementById('filtersPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        function toggleSearch() {
            const panel = document.getElementById('searchPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            if (panel.style.display === 'block') {
                document.getElementById('searchInput').focus();
            }
        }

        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            
            // Filter logic would go here
            showNotification('Filters applied', 'info');
        }

        function searchArchive() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            // Search logic would go here
            console.log('Searching for:', query);
        }

        function refreshRequests() {
            const btn = event.target.closest('.refresh-btn');
            const icon = btn.querySelector('i');
            
            icon.classList.add('fa-spin');
            
            setTimeout(() => {
                icon.classList.remove('fa-spin');
                showNotification('Requests refreshed', 'success');
                loadRequests();
            }, 1500);
        }

        // Profile functions
        function editAvatar() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('profileImage').src = e.target.result;
                        showNotification('Profile picture updated!', 'success');
                    };
                    reader.readAsDataURL(file);
                }
            };
            input.click();
        }

        function updateProfile(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitBtn = form.querySelector('.submit-btn');
            const originalContent = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<div class="loading"></div> Saving...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                showNotification('Profile updated successfully!', 'success');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }, 1500);
        }

        // Export function
        function exportArchive() {
            showNotification('Preparing export...', 'info');
            setTimeout(() => {
                showNotification('Archive exported successfully!', 'success');
            }, 2000);
        }

        // Notifications
        function showNotifications() {
            showNotification('3 new requests require your attention', 'info');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !mobileMenuBtn.contains(e.target) && 
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    </script>

    <style>
        /* Additional styles for new functionality */
        .action-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
            cursor: pointer;
            padding: 8px;
            margin: 0 2px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: scale(1.1);
        }

        .view-btn:hover { color: #4ecdc4; }
        .approve-btn:hover { color: #44bd87; }
        .reject-btn:hover { color: #ff6b6b; }

        .filters-panel, .search-panel {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            min-width: 150px;
        }

        .filter-btn, .refresh-btn, .search-btn, .export-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .filter-btn:hover, .refresh-btn:hover, .search-btn:hover, .export-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .archive-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .archive-stat {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .archive-stat h3 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 10px;
        }

        .archive-stat p {
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }

        .archive-message {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.8);
        }

        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-avatar {
            margin-bottom: 40px;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
        }

        .avatar-container img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
        }

        .edit-avatar-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #4ecdc4;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-avatar-btn:hover {
            transform: scale(1.1);
            background: #44bd87;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 10px;
            border-left: 4px solid #4ecdc4;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 2000;
            animation: slideInRight 0.3s ease;
            color: #333;
            max-width: 350px;
        }

        .notification-success {
            border-left-color: #44bd87;
        }

        .notification button {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            margin-left: auto;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .chart-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .chart-container h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .chart-container canvas {
            width: 100%;
            height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-group {
                flex-direction: column;
            }
            
            .header-actions {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
    </style>
</body>
</html>