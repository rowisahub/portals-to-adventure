document.addEventListener('DOMContentLoaded', function () {
    var hamburgerIcon = document.getElementById('hamburger-icon');
    var sidebarContainer = document.getElementById('sidebar-container');

    if (hamburgerIcon && sidebarContainer) {
        hamburgerIcon.addEventListener('click', function () {
            sidebarContainer.classList.toggle('open');
        });
    }

    // Optionally handle hover with JS if needed
    document.querySelectorAll('.user-info, .submission-item').forEach(function (element) {
        element.addEventListener('mouseenter', function () {
            var options = element.querySelector('.user-options, .submission-options');
            if (options) {
                options.style.display = 'block';
            }
        });

        element.addEventListener('mouseleave', function () {
            var options = element.querySelector('.user-options, .submission-options');
            if (options) {
                options.style.display = 'none';
            }
        });
    });

    // if param submitted_today is set, show message to user that they can only submit once per day
    var urlParams = new URLSearchParams(window.location.search);
    var submittedToday = urlParams.get('submitted_today');
    if (submittedToday) {
        alert('You have already submitted a door today. You can only submit once per day.');
        // remove the param from the URL
        urlParams.delete('submitted_today');
        var newUrl = window.location.origin + window.location.pathname + '?' + urlParams.toString();
        window.history.replaceState({}, document.title, newUrl);
    }
});