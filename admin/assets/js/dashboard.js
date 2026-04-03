
// alert("Dashboard JS Loaded");




document.addEventListener("DOMContentLoaded", function () {

    // Load Project Cards
    fetch('api/project-cards.php')
    .then(res => res.text())
    .then(data => {
        document.getElementById("project-cards").innerHTML = data;
    });

    // Load Active Projects
    fetch('api/active-projects.php')
    .then(res => res.text())
    .then(data => {
        document.getElementById("active-projects").innerHTML = data;
    });

    // Load Projects Table
    fetch('api/projects-table.php')
    .then(res => res.text())
    .then(data => {
        document.getElementById("projects-table").innerHTML = data;
    });

});
