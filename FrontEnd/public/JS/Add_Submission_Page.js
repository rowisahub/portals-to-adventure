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
  //console.log(contest_data);
  if(!contest_data.is_contest_active){
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

    if(!pta_api_data.user_admin){
      document.getElementsByClassName('form-container')[0].classList.add('hide');

      return;
    }

    // Name
    var nameInput = document.getElementById('pta-submission-name');
    // For now we are just changing the name to the user name
    if (nameInput) {
      if(user_data.is_logged_in){
        nameInput.value = user_data.user_name;
      }
    }

    var rulesButton = document.getElementById("pta-rules-popup");
    var popup = document.getElementById("rules-popup");
    var closeBtn = popup.querySelector(".close");

    rulesButton.addEventListener("click", function () {
      popup.style.display = "block";
    });

    closeBtn.addEventListener("click", function () {
      popup.style.display = "none";
    });
  }
});

// All images
var imgUpl = document.getElementById('image-upload');
if (imgUpl) {
  imgUpl.addEventListener('change', function (event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('thumbnail-preview');
    previewContainer.innerHTML = ''; // Clear previous previews
    const currentPreviewCount = previewContainer.children.length;

    var selThub = document.getElementById('selecedThumbnail');
    var selMap = document.getElementById('selecedMap');

    const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
    const maxFiles = 5; // Maximum number of files allowed (you can adjust this as needed)

    if (files.length + currentPreviewCount > maxFiles) {
      alert('You can only upload a maximum of 5 images.');
      return; // Stop if more than 5 files are selected
    }

    Array.from(files).forEach((file, index) => {
      if (currentPreviewCount + index >= 5) return; // Ignore extra files
      if (file.size > maxFileSize) {
        alert(`File ${file.name} is too large. Max size is 10MB.`);
        return; // Skip files larger than 10MB
      }

      console.log(`Processing file ${file.name}, size: ${file.size / 1024 / 1024} MB`);

      selectedFiles.push(file);

      const reader = new FileReader();
      reader.onload = function (e) {
        const previewDiv = document.createElement('div');
        previewDiv.classList.add('image-preview');

        const img = document.createElement('img');
        img.src = e.target.result;
        img.alt = `Uploaded Image ${index + 1}`;
        img.classList.add('preview-image');

        const buttonsDiv = document.createElement('div');
        buttonsDiv.classList.add('buttons');

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.textContent = 'Delete';
        deleteBtn.addEventListener('click', function () {

          const fileIndex = selectedFiles.indexOf(file);
          if (fileIndex > -1) {
            selectedFiles.splice(fileIndex, 1); // Remove file from array
          }

          // update imgUpl with remaining files
          const dataTransfer = new DataTransfer();
          selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
          });
          imgUpl.files = dataTransfer.files;

          previewDiv.remove(); // Remove the preview
        });

        const thumbnailBtn = document.createElement('button');
        thumbnailBtn.type = 'button';
        thumbnailBtn.textContent = 'Thumbnail';

        thumbnailBtn.addEventListener('click', function () {

          // check if image has map class
          if (previewDiv.classList.contains('map')) {
            previewDiv.classList.remove('map');
            selMap.value = '';
          }

          document.querySelectorAll('.thumbnail').forEach(el => el.classList.remove('thumbnail'));
          previewDiv.classList.add('thumbnail'); // Mark as thumbnail

          selThub.value = file.name + '+' + file.size;

          console.log('Set as thumbnail');
        });

        // Create Set as Map button
        const mapBtn = document.createElement('button');
        mapBtn.type = 'button';
        mapBtn.textContent = 'Map';

        mapBtn.addEventListener('click', function () {

          // check if image has thumbnail class
          if (previewDiv.classList.contains('thumbnail')) {
            previewDiv.classList.remove('thumbnail');
            selThub.value = '';
          }

          document.querySelectorAll('.map').forEach(el => el.classList.remove('map'));
          previewDiv.classList.add('map'); // Mark as map

          selMap.value = file.name + '+' + file.size;

          console.log('Set as map');
        });

        previewDiv.appendChild(img);
        buttonsDiv.appendChild(deleteBtn);
        buttonsDiv.appendChild(thumbnailBtn);
        buttonsDiv.appendChild(mapBtn);
        previewDiv.appendChild(buttonsDiv);
        previewContainer.appendChild(previewDiv);
      };

      reader.readAsDataURL(file);
    });
  });
}

// Just one map
var mapUpl = document.getElementById('pta-submission-map');
mapUpl.addEventListener('change', function (event) {
  const files = event.target.files;
  const previewContainer = document.getElementById('map-preview');
  previewContainer.innerHTML = ''; // Clear previous previews
  const currentPreviewCount = previewContainer.children.length;

  const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes

  Array.from(files).forEach((file, index) => {
    if (currentPreviewCount + index >= 5) return; // Ignore extra files
    if (file.size > maxFileSize) {
      alert(`File ${file.name} is too large. Max size is 10MB.`);
      return; // Skip files larger than 10MB
    }

    console.log(`Processing file ${file.name}, size: ${file.size / 1024 / 1024} MB`);

    const reader = new FileReader();
    reader.onload = function (e) {
      const previewDiv = document.createElement('div');
      previewDiv.classList.add('image-preview');

      const img = document.createElement('img');
      img.src = e.target.result;
      img.alt = `Uploaded Image ${index + 1}`;
      img.classList.add('preview-image');

      const buttonsDiv = document.createElement('div');
      buttonsDiv.classList.add('buttons');

      const deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.textContent = 'Delete';
      deleteBtn.addEventListener('click', function () {

        // update imgUpl with remaining files
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
          dataTransfer.items.add(file);
        });
        mapUpl.files = dataTransfer.files;

        previewDiv.remove(); // Remove the preview
      });

      previewDiv.appendChild(img);
      buttonsDiv.appendChild(deleteBtn);
      previewDiv.appendChild(buttonsDiv);
      previewContainer.appendChild(previewDiv);
    };

    reader.readAsDataURL(file);
  });
});

// Just one thumbnail
var thumbnailUpl = document.getElementById('pta-submission-thumbnail');
thumbnailUpl.addEventListener('change', function (event) {
  const files = event.target.files;
  const previewContainer = document.getElementById('thumbnail-preview');
  previewContainer.innerHTML = ''; // Clear previous previews
  const currentPreviewCount = previewContainer.children.length;

  const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes

  Array.from(files).forEach((file, index) => {
    if (currentPreviewCount + index >= 5) return; // Ignore extra files
    if (file.size > maxFileSize) {
      alert(`File ${file.name} is too large. Max size is 10MB.`);
      return; // Skip files larger than 10MB
    }

    console.log(`Processing file ${file.name}, size: ${file.size / 1024 / 1024} MB`);

    const reader = new FileReader();
    reader.onload = function (e) {
      const previewDiv = document.createElement('div');
      previewDiv.classList.add('image-preview');

      const img = document.createElement('img');
      img.src = e.target.result;
      img.alt = `Uploaded Image ${index + 1}`;
      img.classList.add('preview-image');

      const buttonsDiv = document.createElement('div');
      buttonsDiv.classList.add('buttons');

      const deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.textContent = 'Delete';
      deleteBtn.addEventListener('click', function () {

        // update imgUpl with remaining files
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
          dataTransfer.items.add(file);
        });
        thumbnailUpl.files = dataTransfer.files;

        previewDiv.remove(); // Remove the preview
        previewContainer.classList.add('hide');
      });

      previewDiv.appendChild(img);
      buttonsDiv.appendChild(deleteBtn);
      previewDiv.appendChild(buttonsDiv);
      previewContainer.appendChild(previewDiv);

      previewContainer.classList.remove('hide');
    };

    reader.readAsDataURL(file);
  });
});

// Function to validate YouTube URL
function validateYouTubeUrl(url) {
  const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/;
  return youtubeRegex.test(url);
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

// Adding event listener to form submission
document.getElementById("secret-door-form").addEventListener("submit", function (event) {
  // const selMap = document.getElementById('selecedMap').value;
  // const selThumbnail = document.getElementById('selecedThumbnail').value;
  const youtubeUrlInput = document.getElementById('videoURL').value; // Now using 'videoURL' as ID

  // const errorEle = document.getElementById('thumbnail-error');

  const errorVid = document.getElementById('video-error');

  // Validate YouTube URL
  if (!validateYouTubeUrl(youtubeUrlInput)) {
    event.preventDefault(); // Prevent form submission
    errorVid.textContent = 'Please enter a valid YouTube URL.';
    errorVid.classList.remove('hide');
    return; // Stop further form processing
  }

  // Check if terms and conditions are accepted
  if (!termsCheckbox.checked) {
    event.preventDefault(); // Prevent form submission
    alert('Please accept the terms and conditions.');
    return; // Stop further form processing
  }

  // if (!ifNeededSeparateUpload && (!selMap || !selThumbnail)) {
  //   event.preventDefault(); // Prevent form submission

  //   if (!selMap) {
  //     console.log('No map selected');
  //     errorEle.textContent = 'Please select a map image.';
  //     errorEle.classList.remove('hide');
  //   }

  //   if (!selThumbnail) {
  //     console.log('No thumbnail selected');
  //     errorEle.textContent = 'Please select a thumbnail image.';
  //     errorEle.classList.remove('hide');
  //   }

  // } else if (!ifNeededSeparateUpload && (selMap && selThumbnail)) {
  //   errorEle.classList.add('hide');
  // }

});