var loadedSubmissions = null;


document.addEventListener('DOMContentLoaded', async function () {
  submissionChanges();
  try {
    var SelectList = document.getElementById('secret-doors-list');
    var submissions = await WLD_API.getUserSubmissions(pta_api_data.user_id);

    // var testSubmissions = await PTA_API.getSubmissions();

    // console.log("Test Submissions:");
    // console.log(testSubmissions);

    // only show submissions that are 'In Progress', 'Rejected'
    submissions = submissions.filter(function (submission) {
      return submission.state === 'In Progress' || submission.state === 'Rejected';
    });

    loadedSubmissions = submissions;
    console.log("Loaded User Submissions:");
    console.log(submissions);

    var ifFoundSubmissions = false;
    submissions.forEach(function (submission) {

      // check if the submission is 'In Progress' or 'Rejected'
      if (submission.state !== 'In Progress' && submission.state !== 'Rejected') {
        return;
      }
      ifFoundSubmissions = true;

      var ifRejected = false;
      if (submission.state === 'Rejected') ifRejected = true;

      const date = new Date(submission.created_at.replace(' ', 'T'));
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      const formattedDate = date.toLocaleDateString('en-US', options);

      var option = document.createElement('option');
      option.value = submission.id;
      option.text = submission.title + " (" + formattedDate + ")" + (ifRejected ? ' - Rejected' : '');
      SelectList.appendChild(option);
    });

    if (!ifFoundSubmissions) {
      // clear the list and put a message
      SelectList.innerHTML = '<option value="-1">No In Progress submissions found</option>';
    }

    // if param has id of submission, select it
    const urlParams = new URLSearchParams(window.location.search);
    console.log(urlParams);
    const submissionId = urlParams.get('edit_submission_id');
    if (submissionId) {
      SelectList.value = submissionId;
      SelectList.dispatchEvent(new Event('change'));
    }

  } catch (error) {
    console.error('Error fetching submissions:', error);
    alert('Error fetching submissions. Please try again later.');
  }
});

var subId = document.getElementById('submission_id');

var selectedSubmission = null;

document.getElementById("secret-doors-list").addEventListener("change", function () {
  submissionChanges();
  // Load the selected submission details (title, text, map, video, thumbnail) here
  //document.getElementById("save-btn").disabled = true; // Grays out the save button initially
  console.log("Loading submission details...");

  // Get the selected submission ID
  var selectedId = this.value;
  console.log("Selected ID:", selectedId);

  if (!selectedId) {
    console.log("No Valid submission selected");
    // set secret-door-list to value door1
    this.value = 'door1';
    return;
  }

  // Find the selected submission in the loaded submissions
  selectedSubmission = loadedSubmissions.find(function (submission) {
    return submission.id == selectedId;
  });

  console.log("Selected Submission:", selectedSubmission);

  subId.value = selectedSubmission.id;

  if (selectedSubmission.state == 'Rejected') {
    var warningElement = document.getElementById('reject-warning');
    var warningTextEle = document.getElementById('reject-reason');

    warningTextEle.textContent = selectedSubmission.rejected_reason;

    warningElement.classList.remove('hide');
  }

  // Update the form fields with the selected submission details
  document.getElementById("title").value = selectedSubmission.title;
  document.getElementById("adventure-text").value = selectedSubmission.description;
  document.getElementById("video-link").value = selectedSubmission.video_link;

  setImagePreview(selectedSubmission.images);

});

const previewContainer = document.getElementById('thumbnail-preview');

var selThub = document.getElementById('selecedThumbnail');
var selMap = document.getElementById('selecedMap');
var delImg = document.getElementById('deletedImages');

var imgUpl = document.getElementById('images');

// function to set Image preview
var previewImages = [];

var loadedUploadedImages = [];

function setImagePreview(images) {
  console.log("Setting image preview...");

  clearImagePreview();

  images.forEach(function (image) {
    addImageToPreview(image);
  });

}

function clearImagePreview() {
  // Clear the image preview
  console.log("Clearing image preview...");
  previewImages.forEach(function (img) {
    img.imageDiv.remove();
  });
  previewImages = [];
}

/**
 * Adds an image to the preview section with options to delete, set as thumbnail, or set as map.
 *
 * @param {Object} image - The image object to be added to the preview.
 * @param {string} image.image_url - The URL of the image.
 * @param {string} image.id - The unique identifier of the image.
 * @param {number} image.is_thumbnail - Indicates if the image is a thumbnail (1 for true, 0 for false).
 * @param {number} image.is_map - Indicates if the image is a map (1 for true, 0 for false).
 */
function addImageToPreview(image) {
  // Create the image element
  const previewDiv = document.createElement('div');
  previewDiv.classList.add('image-preview');

  const img = document.createElement('img');
  img.src = image.image_url;
  img.alt = image.id;
  img.classList.add('preview-image');

  const buttonsDiv = document.createElement('div');
  buttonsDiv.classList.add('buttons');

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-img-btn');

  deleteBtn.addEventListener('click', function () {
    submissionChanges();
    // Delete the image
    removeImage(image);
    //previewDiv.remove(); // Remove the preview

    //removeImageFromSubmission(image);
  });

  const thumbnailBtn = document.createElement('button');
  thumbnailBtn.type = 'button';
  thumbnailBtn.textContent = 'Thumbnail';
  thumbnailBtn.classList.add('thumbnail-img-btn');

  thumbnailBtn.addEventListener('click', function () {
    submissionChanges();
    // Set the image as the thumbnail

    if (previewDiv.classList.contains('map')) {
      previewDiv.classList.remove('map');
      selMap.value = '';
    }

    document.querySelectorAll('.thumbnail').forEach(el => el.classList.remove('thumbnail'));
    previewDiv.classList.add('thumbnail'); // Mark as thumbnail

    // add new_thumbnail input to the form
    selThub.value = image.id;

    console.log('Set as thumbnail');
  });

  const mapBtn = document.createElement('button');
  mapBtn.type = 'button';
  mapBtn.textContent = 'Map';
  mapBtn.classList.add('map-img-btn');

  mapBtn.addEventListener('click', function () {
    submissionChanges();

    // Set the image as the map
    // This will be implemented in the future

    if (previewDiv.classList.contains('thumbnail')) {
      previewDiv.classList.remove('thumbnail');
      selThub.value = '';
    }

    document.querySelectorAll('.map').forEach(el => el.classList.remove('map'));
    previewDiv.classList.add('map'); // Mark as map

    // add new_map input to the form
    selMap.value = image.id;

    console.log('Set as map');
  });

  if (image.is_thumbnail == 1) {
    previewDiv.classList.add('thumbnail');
  }
  if (image.is_map == 1) {
    previewDiv.classList.add('map');
  }


  previewDiv.appendChild(img);
  buttonsDiv.appendChild(deleteBtn);
  buttonsDiv.appendChild(thumbnailBtn);
  buttonsDiv.appendChild(mapBtn);
  previewDiv.appendChild(buttonsDiv);
  previewContainer.appendChild(previewDiv);

  previewImages.push({ id: image.id, imageData: image, imageDiv: previewDiv });

}

var removedImages = [];

function removeImage(image) {
  // edit map or thumbnail input
  if (selThub.value == image.id) {
    selThub.value = '';
  }
  if (selMap.value == image.id) {
    selMap.value = '';
  }

  var ifImageIsUploaded = false;
  var ifImageIsUploadedIndex = 0;
  loadedUploadedImages.forEach(function (imgDt, index) {
    if (imgDt.id == image.id) {
      ifImageIsUploaded = true;
      ifImageIsUploadedIndex = index;
    }
  });

  if (ifImageIsUploaded) {
    // remove image from uploaded images
    loadedUploadedImages.splice(ifImageIsUploadedIndex, 1);

    // update uploadImages with remaining files
    const dataTransfer = new DataTransfer();

    if (loadedUploadedImages.length == 0) {

      imgUpl.value = '';

    } else {

      loadedUploadedImages.forEach(file => {
        dataTransfer.items.add(file.file);
      });
      imgUpl.files = dataTransfer.files;

    }

  } else {
    // if image is already in the submission
    removedImages.push(image.id);
    //var jsonImgString = JSON.stringify(removedImages);
    delImg.value = removedImages;

  }



  removeImageFromPreview(image)

  console.log("Removed Images: ", removedImages);
}

function removeImageFromPreview(image) {
  // Remove the image from the preview
  console.log("Removing image from preview...");
  var index = previewImages.findIndex(function (img) {
    return img.id == image.id;
  });

  if (index > -1) {
    previewImages[index].imageDiv.remove();
    previewImages.splice(index, 1);
  }

  // Remove the image from the submission
}


document.getElementById("title").addEventListener("input", submissionChanges);
document.getElementById("adventure-text").addEventListener("input", submissionChanges);
// document.getElementById("map-upload").addEventListener("change", enableSaveChanges);
// document.getElementById("video-upload").addEventListener("change", enableSaveChanges);


// Add event listener for the file input

imgUpl.addEventListener("change", function (event) {
  submissionChanges();

  // Check if a submission is selected
  if (!selectedSubmission) {
    alert("Please select a submission first.");
    console.log("No submission selected.");
    // Clear the file input
    this.value = "";
    return;
  }

  console.log("Adding new image(s) to preview...");

  const files = event.target.files;

  const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes

  // Check if the total number of images is more than 5
  if (files.length + previewImages.length > 5) {
    alert('You can only upload a maximum of 5 images.');
    console.log("Too many images selected.");
    // Clear the file input
    this.value = "";
    return;
  }

  // Add the new images to the preview
  Array.from(files).forEach((file) => {
    // uploadedNewImage
    if (file.size > maxFileSize) {
      alert(`File ${file.name} is too large. Max size is 10MB.`);
      console.log("File too large:", file);
      return;
    }

    var imageID = file.name + "+" + file.size;

    var upimdata = {
      id: imageID,
      name: file.name,
      file: file
    }

    loadedUploadedImages.push(upimdata);

    console.log("Adding image to preview:", file);

    const reader = new FileReader();
    reader.onload = function (e) {
      console.log("Reading image file...");
      //console.log(e.target.result);

      addImageToPreview({ id: imageID, image_url: e.target.result, is_thumbnail: 0, is_map: 0 });
    }

    reader.readAsDataURL(file);
  });
});

var acceptTerms = document.getElementById("accept-terms");
var submitBtn = document.getElementById("submit-btn");
var saveBtn = document.getElementById("save-btn");

function submissionChanges() {
  acceptTerms.checked = false;
  submitBtn.disabled = true;
};

// document.getElementById("save-btn").addEventListener("click", function () {
//   // Save the changes (title, text, map, video, thumbnail)
//   this.disabled = true; // Gray out save button after save
// });

// document.getElementById("delete-btn").addEventListener("click", function () {
//   if (confirm("Are you sure you want to delete this adventure?")) {
//     // submit a new form with the submission id and action delete
//     var form = document.getElementById("secret-door-form-update");

//     var action = document.createElement("input");
//     action.setAttribute("type", "submit");
//     action.setAttribute("name", "update_submission");
//     action.setAttribute("value", "delete");

//     form.appendChild(action);

//     form.submit();
//   }
// });

acceptTerms.addEventListener("change", function () {
  // check if submission is selected
  if (!selectedSubmission) {
    alert("Please select a submission first.");
    console.log("No submission selected.");
    // Clear the file input
    this.checked = false;
    return;
  }

  console.log("Accept terms checked:", this.checked);
  submitBtn.disabled = !this.checked;
  saveBtn.disabled = !this.checked;
});

document.getElementById("secret-door-form-update").addEventListener("submit", function (event) {

  // if update_submission is delete, check if terms is accepted
  //console.log(event.submitter);

  var ifDelete = false;

  if (event.submitter.value == "Delete Adventure") {
    if (confirm("Are you sure you want to delete this adventure?")) {
      //
      console.log("Deleting Adventure");

      event.submitter.value = "Delete";

      ifDelete = true;
    }
  }

  if (!acceptTerms.checked && !ifDelete) {
    event.preventDefault();

    alert("You must accept the terms to submit.");
    submitBtn.disabled = true;
    saveBtn.disabled = true;
    return;
  }

  // Check if title, discription, image, video link is entered
  if (document.getElementById("title").value == "" ||
    document.getElementById("adventure-text").value == "" ||
    document.getElementById("video-link").value == "") {
    event.preventDefault();

    alert("Please fill out all fields.");
    return;
  }

  // check if thumbnail and map is selected
  // check in thumbnail-preview, in any div, if it has class thumbnail or map
  var ifThumbnailSelected = false;
  var ifMapSelected = false;
  previewImages.forEach(function (img) {
    if (img.imageDiv.classList.contains('thumbnail')) {
      ifThumbnailSelected = true;
    }
    if (img.imageDiv.classList.contains('map')) {
      ifMapSelected = true;
    }
  });

  if (!ifThumbnailSelected || !ifMapSelected) {
    event.preventDefault();
    alert("Please select a thumbnail and map image.");
    return;
  }

  // check if selecedThumbnail and selecedMap is empty, if so remove element
  if (selThub.value == "") {
    selThub.remove();
  }
  if (selMap.value == "") {
    selMap.remove();
  }
  if (delImg.value == "") {
    delImg.remove();
  }

});
