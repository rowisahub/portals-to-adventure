document.addEventListener('DOMContentLoaded', function() {
  const dataBar = document.getElementsByClassName('pta-data-bar')[0];
  const secureInput = document.querySelectorAll('.pta-secure-input input');
  const registirationForm = document.getElementsByClassName('pta_form_registration')[0];

  if(dataBar) {
    var dataBarContent = document.createElement('p');
    dataBarContent.innerHTML = 'Voting Start Date | February 27, 2025';
    dataBar.appendChild(dataBarContent);

    //  Submissions Start Date = February 20, 2025 / Voting Start Date = February 27, 2025

    // Change the innerHTML of the dataBarContent every 5 seconds
    var count = 0;
    var dataBarContentArray = [
      'Submissions Start Date | February 20, 2025',
      'Voting Start Date | February 27, 2025'
    ];

    setInterval(function() {
      dataBarContent.innerHTML = dataBarContentArray[count];
      count++;
      if (count >= dataBarContentArray.length) {
        count = 0;
      }
    }, 5000);
  }

  if(secureInput) {
    secureInput.forEach(function(input) {
      input.addEventListener('focus', function() {
        this.setAttribute('type', 'text');
      });
      input.addEventListener('blur', function() {
        this.setAttribute('type', 'password');
      });
      // 
    });
  }

  if(registirationForm) {
    // if user is logged in, remove the registration form
    if(user_data.is_logged_in) {
      registirationForm.classList.add('hide');
    }
  }

});