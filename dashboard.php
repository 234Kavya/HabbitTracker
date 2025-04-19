<?php
require_once 'config/database.php';
startSession();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$conn->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

// Get current month and year for calendar
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get user's habits with goals
$stmt = $conn->prepare("
    SELECT 
        h.*,
        GROUP_CONCAT(DISTINCT t.name) as tags,
        GROUP_CONCAT(DISTINCT hfd.day_of_week) as custom_days,
        MAX(g.id) as goal_id,
        MAX(g.title) as goal_title,
        MAX(g.target_value) as target_value,
        MAX(g.current_value) as current_value,
        MAX(g.deadline) as deadline
    FROM habits h
    LEFT JOIN habit_tags ht ON h.id = ht.habit_id
    LEFT JOIN tags t ON ht.tag_id = t.id
    LEFT JOIN habit_frequency_days hfd ON h.id = hfd.habit_id
    LEFT JOIN goals g ON h.id = g.habit_id AND g.status = 'active'
    WHERE h.user_id = ?
    GROUP BY h.id
");
$stmt->execute([getCurrentUserId()]);
$habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get habit completions with notes
$stmt = $conn->prepare("
    SELECT habit_id, completion_date, notes
    FROM habit_completions hc
    JOIN habits h ON h.id = hc.habit_id
    WHERE h.user_id = ?
    AND MONTH(completion_date) = ?
    AND YEAR(completion_date) = ?
");
$stmt->execute([getCurrentUserId(), $currentMonth, $currentYear]);
$completions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get habit templates
$stmt = $conn->prepare("SELECT * FROM habit_templates");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="row">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Habits</h2>
            <div>
                <button class="btn btn-quick-add me-2" data-bs-toggle="modal" data-bs-target="#templatesModal">
                    <i class="bi bi-lightning-fill"></i> Quick Add
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHabitModal">
                    <i class="bi bi-plus-lg"></i> Add Habit
                </button>
            </div>
        </div>

        <div class="row" id="habitsContainer">
            <?php foreach ($habits as $habit): ?>
                <div class="col-md-6 mb-4">
                    <div class="card habit-card <?php echo $habit['priority']; ?>-priority">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title"><?php echo htmlspecialchars($habit['name']); ?></h5>
                                <div class="dropdown">
                                    <button class="btn btn-link text-dark" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item edit-habit" href="#" data-habit-id="<?php echo $habit['id']; ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item view-notes" href="#" data-habit-id="<?php echo $habit['id']; ?>">
                                                <i class="bi bi-journal-text"></i> View Notes
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item set-goal" href="#" data-habit-id="<?php echo $habit['id']; ?>">
                                                <i class="bi bi-trophy"></i> Set Goal
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item delete-habit" href="#" data-habit-id="<?php echo $habit['id']; ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <?php if ($habit['description']): ?>
                                <p class="card-text"><?php echo htmlspecialchars($habit['description']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($habit['goal_id']): ?>
                                <div class="goal-progress mb-2">
                                    <small class="text-muted">Goal: <?php echo htmlspecialchars($habit['goal_title']); ?></small>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo ($habit['current_value'] / $habit['target_value']) * 100; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $habit['current_value']; ?>/<?php echo $habit['target_value']; ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <span class="badge bg-primary"><?php echo ucfirst($habit['frequency']); ?></span>
                                <?php if ($habit['tags']): ?>
                                    <?php foreach (explode(',', $habit['tags']) as $tag): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="calendar-view mb-3">
                                <?php
                                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
                                for ($day = 1; $day <= $daysInMonth; $day++):
                                    $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                    $completion = array_filter($completions, function($c) use ($habit, $date) {
                                        return $c['habit_id'] == $habit['id'] && $c['completion_date'] == $date;
                                    });
                                    $completion = reset($completion);
                                    $isCompleted = !empty($completion);
                                    $hasNotes = $isCompleted && !empty($completion['notes']);
                                    $isToday = $date === date('Y-m-d');
                                ?>
                                    <div class="calendar-day <?php echo $isCompleted ? 'completed' : ''; ?> <?php echo $isToday ? 'today' : ''; ?>"
                                         data-date="<?php echo $date; ?>"
                                         data-habit-id="<?php echo $habit['id']; ?>"
                                         <?php if ($hasNotes): ?>data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($completion['notes']); ?>"<?php endif; ?>>
                                        <?php echo $day; ?>
                                        <?php if ($hasNotes): ?>
                                            <i class="bi bi-journal-text note-indicator"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="streak-badge">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                    <span class="streak-display" data-habit-id="<?php echo $habit['id']; ?>">
                                        Loading streak...
                                    </span>
                                </span>
                                <button class="btn btn-sm btn-outline-primary mark-done-btn"
                                        data-habit-id="<?php echo $habit['id']; ?>"
                                        data-date="<?php echo date('Y-m-d'); ?>">
                                    Mark as Done
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Calendar Navigation</h5>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button class="btn btn-outline-primary" onclick="navigateCalendar('prev')">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <h4 class="mb-0">
                        <?php echo date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)); ?>
                    </h4>
                    <button class="btn btn-outline-primary" onclick="navigateCalendar('next')">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Quick Stats</h5>
                <div class="quick-stats">
                    <div class="stat-item">
                        <span>Total Habits</span>
                        <span class="stat-value" id="totalHabits"><?php echo count($habits); ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Completed Today</span>
                        <span class="stat-value" id="completedToday">0</span>
                    </div>
                    <div class="stat-item">
                        <span>Best Streak</span>
                        <span class="stat-value" id="bestStreak">0 days</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Weekly Review</h5>
                <div class="weekly-review">
                    <p class="text-muted small">Last week's performance</p>
                    <div id="weeklyReview">Loading review...</div>
                </div>
                <button class="btn btn-outline-primary btn-sm w-100" onclick="generateReview('weekly')">
                    <i class="bi bi-arrow-clockwise"></i> Generate Review
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Templates Modal -->
<div class="modal fade" id="templatesModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add Habit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php foreach ($templates as $template): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h6>
                                    <p class="card-text small"><?php echo htmlspecialchars($template['description']); ?></p>
                                    <button class="btn btn-sm btn-outline-primary use-template" 
                                            data-template-id="<?php echo $template['id']; ?>">
                                        Use Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mark Done Modal with Notes -->
<div class="modal fade" id="markDoneModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Habit as Done</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="markDoneForm">
                    <input type="hidden" id="markDoneHabitId">
                    <input type="hidden" id="markDoneDate">
                    
                    <div class="mb-3">
                        <label for="completionNotes" class="form-label">Notes (optional)</label>
                        <textarea class="form-control" id="completionNotes" rows="3" 
                                placeholder="How did it go? Any reflections?"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmMarkDone">Mark as Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Set Goal Modal -->
<div class="modal fade" id="setGoalModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="goalForm">
                    <input type="hidden" id="goalHabitId">
                    
                    <div class="mb-3">
                        <label for="goalTitle" class="form-label">Goal Title</label>
                        <input type="text" class="form-control" id="goalTitle" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="goalDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="goalDescription" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="targetValue" class="form-label">Target Value</label>
                        <input type="number" class="form-control" id="targetValue" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="date" class="form-control" id="deadline" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveGoal">Save Goal</button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Habit Modal -->
<div class="modal fade" id="addHabitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Habit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="habitForm">
                    <input type="hidden" name="habit_id" id="habitId">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Habit Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="frequency" class="form-label">Frequency</label>
                        <select class="form-select" id="frequency" name="frequency" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="custom">Custom Days</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="customDaysContainer" style="display: none;">
                        <label class="form-label">Select Days</label>
                        <div class="btn-group" role="group">
                            <?php
                            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            foreach ($days as $day):
                            ?>
                                <input type="checkbox" class="btn-check" name="custom_days[]" id="<?php echo $day; ?>" value="<?php echo $day; ?>">
                                <label class="btn btn-outline-primary" for="<?php echo $day; ?>">
                                    <?php echo ucfirst(substr($day, 0, 3)); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags</label>
                        <input type="text" class="form-control" id="tags" name="tags" placeholder="Enter tags separated by commas">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveHabit">Save Habit</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize analytics
    fetchAnalytics();
    
    // Handle frequency change
    document.getElementById('frequency').addEventListener('change', function() {
        document.getElementById('customDaysContainer').style.display = 
            this.value === 'custom' ? 'block' : 'none';
    });
    
    // Handle habit form submission
    document.getElementById('saveHabit').addEventListener('click', function() {
        const form = document.getElementById('habitForm');
        if (!validateHabitForm(form)) return;
        
        const formData = new FormData(form);
        const data = {
            name: formData.get('name'),
            description: formData.get('description'),
            frequency: formData.get('frequency'),
            priority: formData.get('priority'),
            tags: formData.get('tags') ? formData.get('tags').split(',').map(tag => tag.trim()) : []
        };
        
        if (formData.get('frequency') === 'custom') {
            data.custom_days = Array.from(document.querySelectorAll('input[name="custom_days[]"]:checked'))
                .map(checkbox => checkbox.value);
        }
        
        const habitId = formData.get('habit_id');
        const method = habitId ? 'PUT' : 'POST';
        const url = 'api/habits.php' + (habitId ? `?id=${habitId}` : '');
        
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error saving habit: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
    
    // Handle habit deletion
    document.querySelectorAll('.delete-habit').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this habit?')) {
                const habitId = this.dataset.habitId;
                fetch(`api/habits.php?id=${habitId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting habit: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
    
    // Handle habit editing
    document.querySelectorAll('.edit-habit').forEach(button => {
        button.addEventListener('click', function() {
            const habitId = this.dataset.habitId;
            const habit = <?php echo json_encode($habits); ?>.find(h => h.id == habitId);
            
            if (habit) {
                document.getElementById('habitId').value = habit.id;
                document.getElementById('name').value = habit.name;
                document.getElementById('description').value = habit.description || '';
                document.getElementById('frequency').value = habit.frequency;
                document.getElementById('priority').value = habit.priority;
                document.getElementById('tags').value = habit.tags || '';
                
                if (habit.frequency === 'custom') {
                    document.getElementById('customDaysContainer').style.display = 'block';
                    const customDays = habit.custom_days ? habit.custom_days.split(',') : [];
                    document.querySelectorAll('input[name="custom_days[]"]').forEach(checkbox => {
                        checkbox.checked = customDays.includes(checkbox.value);
                    });
                }
                
                new bootstrap.Modal(document.getElementById('addHabitModal')).show();
            }
        });
    });
});

// Fetch and update analytics
function fetchAnalytics() {
    fetch('api/analytics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAnalyticsDisplay(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateAnalyticsDisplay(data) {
    // Update streaks
    data.streaks.forEach(streak => {
        const element = document.querySelector(`.streak-display[data-habit-id="${streak.id}"]`);
        if (element) {
            element.textContent = `${streak.current_streak} day${streak.current_streak !== 1 ? 's' : ''} streak`;
        }
    });
    
    // Update quick stats
    document.getElementById('totalHabits').textContent = data.completion_rates.length;
    document.getElementById('completedToday').textContent = 
        data.completion_rates.filter(h => h.days_completed > 0).length;
    
    const bestStreak = Math.max(...data.streaks.map(s => s.best_streak));
    document.getElementById('bestStreak').textContent = 
        `${bestStreak} day${bestStreak !== 1 ? 's' : ''}`;
    
    // Update monthly progress
    const totalCompletions = data.completion_rates.reduce((sum, h) => sum + h.completions, 0);
    const totalPossible = data.completion_rates.reduce((sum, h) => sum + h.total_days, 0);
    const progress = totalPossible > 0 ? (totalCompletions / totalPossible) * 100 : 0;
    
    const progressBar = document.getElementById('monthlyProgress');
    progressBar.style.width = `${progress}%`;
    progressBar.textContent = `${Math.round(progress)}%`;
}
</script>

<?php include 'includes/footer.php'; ?>