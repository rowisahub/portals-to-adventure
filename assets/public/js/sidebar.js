document.addEventListener('DOMContentLoaded', async function () {
    // var hamburgerIcons = document.getElementById('hamburger-icon');
    var sidebarContainer = document.getElementById('pta-sidebar-container');

    if(!sidebarContainer){
        return;
    }

    var hamburgerIcon = sidebarContainer.querySelector('.hamburger-icon');

    var loginButton = document.getElementById('showLogin');

    loginButton.addEventListener('click', function(){
        if(!user_data.is_logged_in){
            var loginPopup = document.getElementById('popup');
            loginPopup.classList.remove('hide');
        }
    });

    if (hamburgerIcon && sidebarContainer) {
        hamburgerIcon.addEventListener('click', function () {
            sidebarContainer.classList.toggle('open');
        });
    }

    // Optionally handle hover with JS if needed
    // document.querySelectorAll('.user-info, .submission-item').forEach(function (element) {
    //     element.addEventListener('mouseenter', function () {
    //         var options = element.querySelector('.user-options, .submission-options');
    //         if (options) {
    //             options.style.display = 'block';
    //         }
    //     });

    //     element.addEventListener('mouseleave', function () {
    //         var options = element.querySelector('.user-options, .submission-options');
    //         if (options) {
    //             options.style.display = 'none';
    //         }
    //     });
    // });

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

    if(user_data.is_logged_in){
        var sidebarUsername = document.getElementById('user-name');
        sidebarUsername.textContent = user_data.user_name + "!";

        loginButton.textContent = 'Logout';
        loginButton.href = user_data.logout_url;

        var sidebarListItems = document.querySelectorAll('#pta-sidebar-container ul li');
        sidebarListItems.forEach(function (item){
            item.classList.remove('hide');
        });

        var userSubmissions = document.getElementById('User-submissions');
        var userSubmissionsInProgress = document.getElementById('user-inprogress-submissions');
        var userSubmissionsSubmitted = document.getElementById('user-submitted-submissions');

        var submissions = await PTA_API.getUserSubmissions(pta_api_data.user_id);

        var inProgressSubmissions = submissions.filter(function(submission){
            return submission.state === "In Progress";
        });

        var submittedSubmissions = submissions.filter(function(submission){
            return submission.state === 'Approved' || submission.state === 'Pending Approval';
        });

        //

        if(inProgressSubmissions.length === 0){
            userSubmissionsInProgress.classList.add('hide');
        } else {
            inProgressSubmissions = inProgressSubmissions.slice(0, 5);

            var ipList = document.getElementById('user-inprogress-submissions-list');
            inProgressSubmissions.forEach(function(submission){
                var listItem = document.createElement('li');
                listItem.classList.add('submission-item');

                var link = document.createElement('a');
                link.href = "/my-in-progress-secret-doors?edit_submission_id=" + submission.id;
                link.textContent = submission.title;

                listItem.appendChild(link);
                ipList.appendChild(listItem);
            });
        }

        if(submittedSubmissions.length === 0){
            userSubmissionsSubmitted.classList.add('hide');
        } else {
            submittedSubmissions = submittedSubmissions.slice(0, 5);

            var ssList = document.getElementById('user-submitted-submissions-list');
            submittedSubmissions.forEach(function(submission){
                // var listItem = document.createElement('li');
                // listItem.classList.add('submission-item');
                // listItem.textContent = submission.title;

                // // Add a link to the submission
                // var link = document.createElement('a');
                // link.classList.add('submission-options');
                // link.href = "/my-submitted-secret-doors?submission_id=" + submission.id;
                // link.textContent = 'View';
                // listItem.appendChild(link);

                // ssList.appendChild(listItem);
                var listItem = document.createElement('li');
                listItem.classList.add('submission-item');
                var link = document.createElement('a');
                link.href = "/my-submitted-secret-doors?submission_id=" + submission.id;
                link.textContent = submission.title;

                listItem.appendChild(link);
                ssList.appendChild(listItem);
            });
        }

        userSubmissions.classList.remove('hide');
    }
});