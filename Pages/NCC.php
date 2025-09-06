<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCC Dashboard</title>
    
<body>
    
    <style>
    body {
        margin: 0;
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #181c2f;
        color: #f1f1f1;
    }
    .ncc-home {
        display: flex;
        min-height: 100vh;
    }
    .ncc-sidebar {
        background: #0a0d1a;
        width: 260px;
        padding: 30px 0;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .ncc-logo img {
        margin-bottom: 40px;
    }
    .ncc-sidebar nav ul {
        list-style: none;
        padding: 0;
        width: 100%;
    }
    .ncc-sidebar nav ul li {
        padding: 18px 30px;
        font-size: 1.2rem;
        color: #b0b8d1;
        cursor: pointer;
        display: flex;
        align-items: center;
    }
    .ncc-sidebar nav ul li i {
        margin-right: 18px;
        font-size: 1.5rem;
    }
    .ncc-sidebar nav ul li:hover, .ncc-sidebar nav ul li.active {
        background: #23294a;
        color: #fff;
    }
    .ncc-main {
        flex: 1;
        padding: 40px 50px;
        background: #181c2f;
    }
    .ncc-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 35px;
    }
    .ncc-header h1 {
        font-size: 2.2rem;
        font-weight: 600;
        color: #fff;
    }
    .ncc-header i {
        font-size: 2rem;
        color: #f7c948;
    }
    .ncc-stats {
        display: flex;
        gap: 30px;
        margin-bottom: 40px;
    }
    .ncc-stat-card {
        background: #23294a;
        border-radius: 12px;
        padding: 28px 32px;
        display: flex;
        align-items: center;
        gap: 18px;
        min-width: 220px;
        box-shadow: 0 2px 8px #0002;
    }
    .ncc-stat-card i {
        font-size: 2.5rem;
        color: #4fd1c5;
    }
    .ncc-stat-card h2 {
        font-size: 1.1rem;
        margin: 0 0 6px 0;
        color: #b0b8d1;
    }
    .ncc-stat-card span {
        font-size: 2rem;
        font-weight: bold;
        color: #fff;
    }
    .ncc-documents {
        margin-top: 30px;
    }
    .ncc-documents h2 {
        margin-bottom: 18px;
        color: #f7c948;
    }
    .ncc-documents table {
        width: 100%;
        border-collapse: collapse;
        background: #23294a;
        border-radius: 10px;
        overflow: hidden;
    }
    .ncc-documents th, .ncc-documents td {
        padding: 14px 12px;
        text-align: left;
    }
    .ncc-documents th {
        background: #23294a;
        color: #4fd1c5;
        font-weight: 600;
    }
    .ncc-documents td {
        border-top: 1px solid #23294a;
        color: #fff;
    }
    .status {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.95rem;
        font-weight: 500;
    }
    .status.new { background: #4fd1c5; color: #181c2f; }
    .status.reviewed { background: #f7c948; color: #181c2f; }
    .status.responded { background: #38b2ac; color: #fff; }
    .ncc-section {
        display: block;
    }
    .ncc-form-group {
        margin-bottom: 22px;
    }
    .ncc-form-group label {
        display: block;
        margin-bottom: 7px;
        color: #b0b8d1;
        font-size: 1.1rem;
    }
    .ncc-form-group input, .ncc-form-group select, .ncc-form-group textarea {
        width: 100%;
        padding: 12px;
        border-radius: 7px;
        border: none;
        background: #23294a;
        color: #fff;
        font-size: 1rem;
        margin-top: 3px;
    }
    .ncc-btn {
        background: #4fd1c5;
        color: #181c2f;
        border: none;
        padding: 12px 38px;
        border-radius: 25px;
        font-size: 1.2rem;
        font-weight: 600;
        cursor: pointer;

    }
    .ncc-btn:hover {
        background: #38b2ac;
    }
    @media (max-width: 900px) {
        .ncc-home { flex-direction: column; }
        .ncc-sidebar { width: 100%; flex-direction: row; justify-content: space-around; }
        .ncc-main { padding: 20px 10px; }
        .ncc-stats { flex-direction: column; gap: 18px; }
    }
    </style>

    <div class="ncc-home">
        <!-- Sidebar -->
        <aside class="ncc-sidebar">
            <div class="ncc-logo">
                <img src="../images/ReqZone2.png" alt="NCC Logo" width="120">
            </div>
            <nav>
                <ul>
                    <li onclick="displayDashboard()"><i class="fa-solid fa-inbox"></i> Received Docs</li>
                    <li onclick="displayUpload()"><i class="fa-solid fa-paper-plane"></i> Internal Memo</li>
                    <li onclick="displayRecords()"><i class="fa-solid fa-archive"></i> Archive</li>
                    <li onclick="displayProfile()"><i class="fa-solid fa-user-shield"></i> Profile</li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="ncc-main">
            <!-- Dashboard -->
            <section id="Dashboard_content" class="ncc-section">
                <header class="ncc-header">
                    <h1>Welcome, NCC Officer</h1>
                    <i class="fa-solid fa-bell"></i>
                </header>
                <div class="ncc-stats">
                    <div class="ncc-stat-card">
                        <i class="fa-solid fa-envelope-open-text"></i>
                        <div>
                            <h2>New Facility Submissions</h2>
                            <span>3</span>
                        </div>
                    </div>
                    <div class="ncc-stat-card">
                        <i class="fa-solid fa-reply"></i>
                        <div>
                            <h2>Responses Sent</h2>
                            <span>1</span>
                        </div>
                    </div>
                    <div class="ncc-stat-card">
                        <i class="fa-solid fa-memo-circle-info"></i>
                        <div>
                            <h2>Internal Memos</h2>
                            <span>2</span>
                        </div>
                    </div>
                </div>
                <div class="ncc-documents">
                    <h2>Recently Received Documents</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Facility A</td>
                                <td>Payment Request Q3</td>
                                <td>Payment</td>
                                <td>2025-08-28</td>
                                <td><span class="status new">New</span></td>
                            </tr>
                            <tr>
                                <td>Facility B</td>
                                <td>Job Request - IT Support</td>
                                <td>Job</td>
                                <td>2025-08-27</td>
                                <td><span class="status reviewed">Reviewed</span></td>
                            </tr>
                            <tr>
                                <td>Facility C</td>
                                <td>Payment Request Q2</td>
                                <td>Payment</td>
                                <td>2025-08-25</td>
                                <td><span class="status responded">Responded</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Upload Internal Memo/Response -->
            <section class="ncc-section" id="Upload" style="display:none;">
                <h1>Send Internal Memo / Response</h1>
                <form>
                    <div class="ncc-form-group">
                        <label>Memo Title</label>
                        <input type="text" name="memo-title" placeholder="Enter memo or response title..." required>
                    </div>
                    <div class="ncc-form-group">
                        <label>Recipient</label>
                        <select name="recipient" required>
                            <option value="">Select recipient</option>
                            <option value="Facility A">Facility A</option>
                            <option value="Facility B">Facility B</option>
                            <option value="Internal">Internal (NCC Staff)</option>
                        </select>
                    </div>
                    <div class="ncc-form-group">
                        <label>Message</label>
                        <textarea name="memo-message" rows="8" placeholder="Type your memo or response..." required></textarea>
                    </div>
                    <div class="ncc-form-group">
                        <label>Attach File (optional)</label>
                        <input type="file" name="memo-attachment">
                    </div>
                    <button type="submit" class="ncc-btn">Send</button>
                </form>
            </section>

            <!-- Archive/Records -->
            <section class="ncc-section" id="Record" style="display:none;">
                <h1>Archive</h1>
                <p>All processed documents and memos will appear here.</p>
            </section>

            <!-- Profile -->
            <section class="ncc-section" id="Profile" style="display:none;">
                <h1>Profile</h1>
                <p>Your NCC officer profile information will show here.</p>
            </section>
        </main>
    </div>

    <script>
        function showSection(sectionId) {
            document.getElementById('Dashboard_content').style.display = 'none';
            document.getElementById('Upload').style.display = 'none';
            document.getElementById('Record').style.display = 'none';
            document.getElementById('Profile').style.display = 'none';
            document.getElementById(sectionId).style.display = 'block';
        }
        function displayDashboard() { showSection('Dashboard_content'); }
        function displayUpload() { showSection('Upload'); }
        function displayRecords() { showSection('Record'); }
        function displayProfile() { showSection('Profile'); }
    </script>
</body>
</html>