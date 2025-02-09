document.addEventListener('DOMContentLoaded', function() {
  const dataBar = document.getElementsByClassName('pta-data-bar')[0];

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


});