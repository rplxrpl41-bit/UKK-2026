body {
    background: #f4f6f9;
    font-family: Arial, sans-serif;
    color: #1e293b;
    margin: 0;
}

.sidebar {
    background: #172554;
    color: white;
    min-height: 100vh;
    padding: 22px 15px;
}

.sidebar-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 30px;
    padding: 0 10px;
    line-height: 1.5;
}

.sidebar .nav-link {
    color: #e2e8f0;
    padding: 12px 14px;
    margin-bottom: 6px;
    border-radius: 6px;
    transition: 0.2s;
}

.sidebar .nav-link:hover {
    background: #263b70;
    color: white;
}

.sidebar .nav-link.active {
    background: #3157d5;
    color: white;
}

.sidebar .nav-link.logout {
    color: #fca5a5;
}

.sidebar hr {
    border-color: #64748b;
}

.main-content {
    padding: 28px;
}

.page-title {
    color: #172554;
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 6px;
}

.page-description {
    color: #64748b;
    margin-bottom: 25px;
}

.content-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
    max-width: 850px;
}

.form-label {
    color: #172554;
    font-weight: 600;
}

.alert {
    border-radius: 8px;
}

@media (max-width: 767px) {
    .sidebar {
        min-height: auto;
    }

    .main-content {
        padding: 18px;
    }
}
