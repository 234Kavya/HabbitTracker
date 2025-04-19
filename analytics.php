<?php
require_once 'config/database.php';
startSession();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h2>Analytics Dashboard</h2>
    </div>
</div>

<div class="row">
    <!-- Completion Rate Chart -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Completion Rate (Last 30 Days)</h5>
                <div class="chart-container">
                    <canvas id="completionRateChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Weekly Progress Chart -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Weekly Progress</h5>
                <div class="chart-container">
                    <canvas id="weeklyProgressChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Priority Distribution -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Priority Distribution</h5>
                <div class="chart-container">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tag Performance -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Tag Performance</h5>
                <div class="chart-container">
                    <canvas id="tagPerformanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Streak Statistics -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Streak Statistics</h5>
                <div class="table-responsive">
                    <table class="table" id="streakTable">
                        <thead>
                            <tr>
                                <th>Habit</th>
                                <th>Current Streak</th>
                                <th>Best Streak</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('api/analytics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCharts(data.data);
                updateStreakTable(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
});

function updateCharts(data) {
    // Completion Rate Chart
    const completionCtx = document.getElementById('completionRateChart').getContext('2d');
    new Chart(completionCtx, {
        type: 'bar',
        data: {
            labels: data.completion_rates.map(h => h.name),
            datasets: [{
                label: 'Completion Rate (%)',
                data: data.completion_rates.map(h => 
                    Math.round((h.days_completed / h.total_days) * 100)
                ),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // Weekly Progress Chart
    const weeklyCtx = document.getElementById('weeklyProgressChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'line',
        data: {
            labels: data.weekly_completions.map(w => `Week ${w.week}`),
            datasets: [{
                label: 'Habits Completed',
                data: data.weekly_completions.map(w => w.habits_completed),
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Priority Distribution Chart
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    new Chart(priorityCtx, {
        type: 'doughnut',
        data: {
            labels: data.priority_stats.map(p => p.priority),
            datasets: [{
                data: data.priority_stats.map(p => p.completions),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Tag Performance Chart
    const tagCtx = document.getElementById('tagPerformanceChart').getContext('2d');
    new Chart(tagCtx, {
        type: 'bar',
        data: {
            labels: data.tag_stats.map(t => t.tag),
            datasets: [{
                label: 'Completion Rate (%)',
                data: data.tag_stats.map(t => 
                    Math.round((t.completions / (t.total_habits * 30)) * 100)
                ),
                backgroundColor: 'rgba(153, 102, 255, 0.5)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function updateStreakTable(data) {
    const tbody = document.querySelector('#streakTable tbody');
    tbody.innerHTML = '';
    
    data.streaks.forEach(streak => {
        const habit = data.completion_rates.find(h => h.id === streak.id);
        const completionRate = habit ? 
            Math.round((habit.days_completed / habit.total_days) * 100) : 0;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${habit.name}</td>
            <td>${streak.current_streak} days</td>
            <td>${streak.best_streak} days</td>
            <td>${completionRate}%</td>
        `;
        tbody.appendChild(row);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
