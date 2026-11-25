<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="main-header-nav" style="background-color: #ffffff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-bottom: 1px solid #eef2f5; font-family: 'Segoe UI', sans-serif;">
    
    <div class="logo-area">
        <a href="index.php?page=1" style="text-decoration: none; font-size: 20px; font-weight: 700; color: #1e293b; letter-spacing: -0.5px;">
            🎓 GMI <span style="color: #3b82f6;">Marketplace</span> 
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 3): ?>
                <span style="background-color: #ef4444; color: white; font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">ADMIN</span>
            <?php endif; ?>
        </a>
    </div>

    <div class="nav-links-area" style="display: flex; gap: 20px; align-items: center;">
        <a href="index.php?page=1" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">🏠 Home</a>
        
        <?php if (isset($_SESSION['userid'])): ?>

            <?php if ($_SESSION['role'] == 3): ?>
                <a href="index.php?page=8" style="
                    background-color: #3b82f6;
                    color: #ffffff;
                    padding: 8px 16px;
                    border-radius: 6px;
                    font-weight: 600;
                    text-decoration: none;
                    font-size: 13px;
                ">📊 Admin Dashboard</a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] != 3): ?>
                <a href="index.php?page=3" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">➕ Add Item</a>

                <a href="index.php?page=4" style="text-decoration: none; color: #3b82f6; font-weight: 600; font-size: 14px; background-color: #eff6ff; padding: 6px 12px; border-radius: 6px;">
                    📋 Order History
                </a>
            <?php endif; ?>
            
            <a href="index.php?page=7" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">👤 Profile</a>
            
            <a href="logout.php" style="text-decoration: none; color: #ef4444; font-weight: 600; font-size: 14px;">🚪 Logout</a>

        <?php elseif (isset($_SESSION['guest']) && $_SESSION['guest'] === true): ?>
            <a href="login.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">🔑 Login</a>
            <a href="registrationform.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">📝 Register</a>

        <?php else: ?>
            <a href="login.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">🔑 Login</a>
        <?php endif; ?>
    </div>
</div>