<?php
require_once 'config/database.php';
startSession();

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-4 mb-4">Welcome to Habit Tracker</h1>
            <p class="lead mb-4">Track your daily habits, build streaks, and achieve your goals!</p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="mb-4">
                    <a href="register.php" class="btn btn-primary btn-lg me-3">Get Started</a>
                    <a href="login.php" class="btn btn-outline-primary btn-lg">Login</a>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <a href="dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-check display-4 text-primary mb-3"></i>
                    <h3>Track Daily</h3>
                    <p>Keep track of your habits with our easy-to-use daily tracking system.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up display-4 text-primary mb-3"></i>
                    <h3>View Progress</h3>
                    <p>Monitor your progress with detailed analytics and visual charts.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-lightning-charge display-4 text-primary mb-3"></i>
                    <h3>Build Streaks</h3>
                    <p>Stay motivated by building and maintaining streaks for your habits.</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!isLoggedIn()): ?>
        <div class="row mt-5">
            <div class="col-12 text-center">
                <h2 class="mb-4">Ready to start building better habits?</h2>
                <a href="register.php" class="btn btn-primary btn-lg">Create Your Account</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>