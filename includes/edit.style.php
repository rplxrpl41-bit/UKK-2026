/* =====================================================
   GAYA HALAMAN EDIT (disamakan dengan Edit Data Guru)
   Dipakai bersama includes/crud_style.php (gaya sidebar).
   ===================================================== */
body {
    background: #f4f6f9;
    font-family: Arial, sans-serif;
    margin: 0;
}

.content {
    padding: 28px;
}

.page-title {
    color: #172554;
    font-weight: bold;
    margin-bottom: 8px;
}

.card-main {
    background: white;
    border: none;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
}

.card-main .card-header {
    font-weight: bold;
    color: #172554;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
}

.section-title {
    color: #172554;
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 14px;
}

.btn-primary {
    background: #3157d5;
    border-color: #3157d5;
}

.btn-primary:hover {
    background: #2445b8;
    border-color: #2445b8;
}

.footer {
    color: #94a3b8;
    font-size: 13px;
}

@media (max-width: 767px) {
    .content {
        padding: 15px;
    }
}
