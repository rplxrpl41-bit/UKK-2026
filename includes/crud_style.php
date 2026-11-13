body {
    background: #f4f6f9;
    font-family: Arial, sans-serif;
    color: #1e293b;
    margin: 0;
}

.sidebar {
    background: #172554;
    color: white
/* =====================================
   STYLE DASAR
===================================== */
* {
    box-sizing: border-box;
}

body {
    background: #f4f6f9;
    font-family: Arial, sans-serif;
    color: #1e293b;
    margin: 0;
}

/* =====================================
   LAYOUT UTAMA
===================================== */
.container-fluid > .row {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    margin-left: 0;
    margin-right: 0;
}

/* =====================================
   SIDEBAR
===================================== */
.row > .sidebar {
    background: #172554;
    color: white;

    flex: 0 0 250px;
    width: 250px;
    max-width: 250px;

    min-height: 100vh;
    height: 100vh;
    padding: 22px 12px;

    position: sticky;
    top: 0;
    align-self: flex-start;
    overflow-y: auto;
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
    display: block;
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

/* =====================================
   KONTEN UTAMA
===================================== */
.row > .main-content {
    flex: 1 1 0;
    width: auto;
    max-width: none;
    min-width: 0;

    padding: 28px;
    background: #f4f6f9;
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

/* =====================================
   CARD FORM
===================================== */
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

/* =====================================
   RESPONSIVE
===================================== */
@media (max-width: 767px) {

    .container-fluid > .row {
        flex-wrap: wrap;
    }

    .row > .sidebar {
        flex: 0 0 100%;
        width: 100%;
        max-width: 100%;

        min-height: auto;
        height: auto;

        position: relative;
        top: auto;
        overflow-y: visible;
    }

    .row > .main-content {
        flex: 0 0 100%;
        width: 100%;
        padding: 18px;
    }

    .content-card {
        padding: 18px;
    }
}
;
    min-height: 100vh;
    padding: 22px 15px;

    /* Sidebar diam di tempat saat halaman di-scroll */
    position: sticky;
    top: 0;
    height: 100vh;
    align-self: flex-start;
    overflow-y: auto;
}

/* Kolom pembungkus sidebar ikut berwarna agar penuh sepanjang halaman */
.row > div:has(> .sidebar) {
    background: #172554;
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
        position: static;
        height: auto;
        overflow-y: visible;
        align-self: auto;
    }

    .main-content {
        padding: 18px;
    }
}
