document.addEventListener('DOMContentLoaded', function () {
    //
    var popup = document.getElementById('popup');
    var closePopupBtn = document.getElementById('closePopupBtn');
    var promoEmails = document.getElementById('promotionalEmails');
    var showLoginBtn = document.getElementById('showLogin');
    var sidebarContainer = document.getElementById('sidebar-container');

    // check if closePopupBtn exists
    if (closePopupBtn === null) {
        //console.log('closePopupBtn not found! User is already logged in.');
        return;
    }

    closePopupBtn.onclick = function () {
        popup.style.display = 'none';
    }

    showLoginBtn.onclick = function () {
        console.log('showLoginBtn clicked');
        //event.preventDefault();
        popup.style.display = 'flex';

        // close sidebar if open
        if (sidebarContainer.classList.contains('open')) {
            sidebarContainer.classList.remove('open');
        }
    }

    // Ensure the popup covers the entire viewport
    function ensurePopupCoversViewport() {
        //popup.style.width = (window.innerWidth) + 'px';
        // popup.style.left = (window.innerWidth / 2) + 'px';
        //popup.style.left = window.innerWidth;
        //popup.style.height = (window.innerHeight) + 'px';
        console.log('Window width: ' + window.innerWidth);
        console.log('Window height: ' + window.innerHeight);
    }

    // Call the function on load and on resize
    //ensurePopupCoversViewport();
    //window.addEventListener('resize', ensurePopupCoversViewport);

    function handleCredentialResponse(response) {

        var bodyData = new URLSearchParams();
        bodyData.append('action', 'wldpta_google_login');
        bodyData.append('credential', response.credential);

        if (promoEmails.checked) {
            bodyData.append('promotionalEmails', '1');
        }

        fetch(ajax_object.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: bodyData.toString()
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Google login successful!');
                    popup.style.display = 'none';

                    window.location.href = window.location.href + '?pta-login=true';

                } else {
                    console.log('Google login failed: ' + data.data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    window.onload = function () {
        google.accounts.id.initialize({
            client_id: '322331838115-fhp6ql51sqb6ounq5psj1rm83385j449.apps.googleusercontent.com',
            callback: handleCredentialResponse,
            auto_select: true,
            ux_mode: 'popup',
        });
        google.accounts.id.renderButton(
            document.getElementById('loginGoogleBtn'),
            { theme: 'outline', size: 'large', text: 'continue_with' }
        );
    }
});