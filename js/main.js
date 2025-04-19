// Habit completion toggle
document.addEventListener('DOMContentLoaded', function() {
    // Handle habit completion toggles
    const completionButtons = document.querySelectorAll('.habit-complete-btn');
    completionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const habitId = this.dataset.habitId;
            const date = this.dataset.date || new Date().toISOString().split('T')[0];
            
            fetch('api/habit_completion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    habit_id: habitId,
                    date: date
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('completed');
                    updateStreakDisplay(habitId, data.streak);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Update streak display
function updateStreakDisplay(habitId, streak) {
    const streakElement = document.querySelector(`.streak-display[data-habit-id="${habitId}"]`);
    if (streakElement) {
        streakElement.textContent = `${streak} day${streak !== 1 ? 's' : ''} streak`;
    }
}

// Calendar navigation
function navigateCalendar(direction) {
    const currentMonth = document.querySelector('.calendar-month').dataset.month;
    const currentYear = document.querySelector('.calendar-year').dataset.year;
    
    let newDate = new Date(currentYear, currentMonth - 1);
    if (direction === 'prev') {
        newDate.setMonth(newDate.getMonth() - 1);
    } else {
        newDate.setMonth(newDate.getMonth() + 1);
    }
    
    window.location.href = `?month=${newDate.getMonth() + 1}&year=${newDate.getFullYear()}`;
}

// Analytics chart initialization
function initAnalyticsChart(canvasId, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Form validation
function validateHabitForm(form) {
    const name = form.querySelector('[name="name"]').value.trim();
    const frequency = form.querySelector('[name="frequency"]').value;
    
    if (!name) {
        alert('Please enter a habit name');
        return false;
    }
    
    if (frequency === 'custom') {
        const customDays = form.querySelectorAll('[name="custom_days[]"]:checked');
        if (customDays.length === 0) {
            alert('Please select at least one day for custom frequency');
            return false;
        }
    }
    
    return true;
} 