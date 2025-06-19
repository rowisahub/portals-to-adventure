const selectedFiles = []; // Array to keep track of selected files

// document.getElementById("secret-door-form").addEventListener("submit", function (event) {
//   event.preventDefault();

//   const inputElement = document.getElementById('image-upload');
//   const dataTransfer = new DataTransfer();

//   selectedFiles.forEach(file => {
//       dataTransfer.items.add(file);
//   });

//   // Update the file input with the new filtered file list
//   inputElement.files = dataTransfer.files;



//   // If validation passes, submit the form and redirect
//   //window.location.href = "/my-in-progress-secret-doors";
//   // this.submit();
// });

document.addEventListener('DOMContentLoaded', async function () {
  // console.log("Add Submission Page JS Loaded");
  if(!contest_data.is_contest_active){
    // console.log("Contest is not active");
    // Show the contest over message with alert
    var contestState = contest_data.contest_state; // active, pre, post
    var contest_start_date = new Date(contest_data.contest_start_date);
    var contest_end_date = new Date(contest_data.contest_end_date);
  
    var contestOverMessage = document.createElement('div');
    contestOverMessage.classList.add('contest-warning');
    contestOverMessage.classList.add('text-center');
    contestOverMessage.classList.add('mt-4');
    contestOverMessage.classList.add('mb-4');
    contestOverMessage.innerHTML = '';

    if(contestState === 'pre'){
      contestOverMessage.innerHTML = 'The contest has not started. Please come back ' + contest_start_date.toLocaleString();
    } else if(contestState === 'post'){
      contestOverMessage.innerHTML = 'The contest ended ' + contest_end_date.toLocaleString() + '. Thank you for participating.';
    }

    // add the message to 'entry-content' as the first child
    document.getElementsByClassName('entry-content')[0].insertBefore(contestOverMessage, document.getElementsByClassName('entry-content')[0].firstChild);

  } else {
    // console.log("Contest is active");
    // Name
    var nameInput = document.getElementById('pta-submission-name');
    // For now we are just changing the name to the user name
    if (nameInput) {
      if(user_data.is_logged_in){
        nameInput.value = user_data.user_name;
      }
    }

    var rulesButton = document.getElementById("pta-rules-popup");

    if(rulesButton){
      rulesButton.addEventListener("click", function (e) {
        e.preventDefault();
        const footerButtons = document.querySelectorAll('#pta-footer button');
        footerButtons.forEach(button => {
          if (button.textContent.trim() === 'Rules') {
            button.click();
          }
        });
      });
    }

    const termsCheckbox = document.getElementById('pta-submission-terms');
    const submitButton = document.getElementById('pta-submit-button');
    // Check if the terms checkbox exists, if so, add an event listener when the checkbox is clicked
    if (termsCheckbox) {
      termsCheckbox.addEventListener('click', function () {
        if (termsCheckbox.checked) {
          console.log('Terms and conditions accepted');
          // Enable the submit button
          submitButton.removeAttribute('disabled');
        } else {
          console.log('Terms and conditions not accepted');
          // Disable the submit button
          submitButton.setAttribute('disabled', 'disabled');
        }
      });
    }
  }

  const defaultContent = 'This is a great adventure...';

  var tinyMDE1 = new TinyMDE.Editor({
    textarea: 'pta-submission-description',
    content: defaultContent,
  });
  var commandBar1 = new TinyMDE.CommandBar({
    element: 'tinymde_commandbar1',
    editor: tinyMDE1,
    commands: [
      'bold', 'italic', 'strikethrough', '|', 'h1', 'h2', '|', 'ul', 'ol', '|', 'blockquote', 'hr', '|', 'code'
    ]
  });

  tinyMDE1.addEventListener('change', (event) => {
    // Save the content to the textarea
    console.log('Content changed:', event.content);  

    const textarea = document.getElementById('pta-submission-description');
    if (textarea) {
      console.log('Textarea found:', textarea.value);
    }

  });

  // on submit

  document.getElementById("secret-door-form").addEventListener("submit", function (event) {
    // show content of description textarea
    const descriptionTextarea = document.getElementById('pta-submission-description');
    if (descriptionTextarea) {
      console.log('Description Textarea content:', descriptionTextarea.value);
      // Check if textarea has content or if default content is present
      if (descriptionTextarea.value.trim() === '' || descriptionTextarea.value === defaultContent) {
        event.preventDefault(); // Prevent form submission
        alert('Please enter a valid description for your adventure.');
        return; // Stop further form processing
      }
    }

    // Check if terms and conditions are accepted
    if (!termsCheckbox.checked) {
      event.preventDefault(); // Prevent form submission
      alert('Please accept the terms and conditions.');
      return; // Stop further form processing
    }
  });
});

// Function to validate YouTube URL
function validateYouTubeUrl(url) {
  const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/;
  return youtubeRegex.test(url);
}

// TinyMDE.init({
//   selector: '#pta-submission-description',
//   setup: function (editor) {
//     editor.on('change', function () {
//       editor.save(); // Save the content to the textarea
//     });
//   }
// });

// Adding event listener to form submission
// document.getElementById("secret-door-form").addEventListener("submit", function (event) {
  // const selMap = document.getElementById('selecedMap').value;
  // const selThumbnail = document.getElementById('selecedThumbnail').value;
  // const youtubeUrlInput = document.getElementById('videoURL').value; // Now using 'videoURL' as ID

  // // const errorEle = document.getElementById('thumbnail-error');

  // const errorVid = document.getElementById('video-error');

  // // Validate YouTube URL
  // if (!validateYouTubeUrl(youtubeUrlInput)) {
  //   event.preventDefault(); // Prevent form submission
  //   errorVid.textContent = 'Please enter a valid YouTube URL.';
  //   errorVid.classList.remove('hide');
  //   return; // Stop further form processing
  // }

  // // Check if terms and conditions are accepted
  // if (!termsCheckbox.checked) {
  //   event.preventDefault(); // Prevent form submission
  //   alert('Please accept the terms and conditions.');
  //   return; // Stop further form processing
  // }

// });