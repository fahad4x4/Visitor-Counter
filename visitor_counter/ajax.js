document.addEventListener("DOMContentLoaded", function() {
    fetch("plugins/visitor_counter/stats.php")
        .then(response => response.json())
        .then(data => {
            document.getElementById("today-count").textContent = data.today;
            document.getElementById("week-count").textContent = data.weekly;
            document.getElementById("month-count").textContent = data.monthly;
            document.getElementById("total-count").textContent = data.total;
        });
});
