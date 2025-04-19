document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle mark as done
    document.querySelectorAll('.mark-done-btn').forEach(button => {
        button.addEventListener('click', function() {
            const habitId = this.dataset.habitId;
            const date = this.dataset.date;
            
            document.getElementById('markDoneHabitId').value = habitId;
            document.getElementById('markDoneDate').value = date;
            document.getElementById('completionNotes').value = '';
            
            new bootstrap.Modal(document.getElementById('markDoneModal')).show();
        });
    });
    
    // Handle mark done confirmation
    document.getElementById('confirmMarkDone').addEventListener('click', function() {
        const habitId = document.getElementById('markDoneHabitId').value;
        const date = document.getElementById('markDoneDate').value;
        const notes = document.getElementById('completionNotes').value;
        
        fetch('api/completions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                habit_id: habitId,
                date: date,
                notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error marking habit as done: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
    
    // Handle template usage
    document.querySelectorAll('.use-template').forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.dataset.templateId;
            
            fetch('api/habits.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    template_id: templateId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error creating habit from template: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // Handle goal setting
    document.querySelectorAll('.set-goal').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const habitId = this.dataset.habitId;
            document.getElementById('goalHabitId').value = habitId;
            new bootstrap.Modal(document.getElementById('setGoalModal')).show();
        });
    });
    
    // Handle goal saving
    document.getElementById('saveGoal').addEventListener('click', function() {
        const habitId = document.getElementById('goalHabitId').value;
        const data = {
            habit_id: habitId,
            title: document.getElementById('goalTitle').value,
            description: document.getElementById('goalDescription').value,
            target_value: document.getElementById('targetValue').value,
            deadline: document.getElementById('deadline').value
        };
        
        fetch('api/goals.php', {
            method: 'POST',
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
                alert('Error saving goal: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
    
    // Generate review
    window.generateReview = function(type) {
        fetch(`api/reviews.php?type=${type}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const reviewDiv = document.getElementById('weeklyReview');
                    reviewDiv.innerHTML = `
                        <div class="mb-2">
                            <strong>Most Followed:</strong><br>
                            ${data.most_followed}
                        </div>
                        <div class="mb-2">
                            <strong>Need Improvement:</strong><br>
                            ${data.most_missed}
                        </div>
                        <div>
                            <strong>Suggestions:</strong><br>
                            ${data.suggestions}
                        </div>
                    `;
                }
            })
            .catch(error => console.error('Error:', error));
    };
    
    // Initialize analytics
    fetchAnalytics();
}); 