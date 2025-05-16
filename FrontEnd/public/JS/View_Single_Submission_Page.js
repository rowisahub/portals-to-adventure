var loadedSubmission = null;
var ifAdmin = false;

document.addEventListener('DOMContentLoaded', async function () {

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
  }

  try {

    ifAdmin = pta_api_data.user_admin;

    const urlParams = new URLSearchParams(window.location.search);
    console.log(urlParams);
    const submissionId = urlParams.get('id');

    console.log("Submission ID:", submissionId);

    if (!submissionId) {
      alert('No submission ID provided.');
      return;
    }

    var submission = await PTA_API.getSubmissionDetails(submissionId);


    loadedSubmission = submission;

    showSubmissionDetails(submission);

  } catch (error) {
    console.error('Error fetching submissions:', error.responseJSON);
    alert('Error fetching submissions: ' + error.responseJSON.message);
  }
});

function showSubmissionDetails(submission) {
  // 
  try {
    console.log("Showing submission details...");
    console.log(submission);

    const date = new Date(submission.created_at.replace(' ', 'T'));
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = date.toLocaleDateString('en-US', options);
    submission.created_at_formatted = formattedDate;

    const videoID = getYouTubeVideoID(submission.video_link);
    const videoThumbnailLink = `https://img.youtube.com/vi/${videoID}/hqdefault.jpg`;

    // Get the elements to update
    var authorElement = document.getElementById('adventure-author');
    var titleElement = document.getElementById('adventure-title');
    var descriptionElement = document.getElementById('adventure-text');

    var mapElement = document.getElementById('map-preview');
    var videoElement = document.getElementById('video-preview');
    var thumbnailElement = document.getElementById('thumbnail-preview');

    var submissionData = document.getElementById('date-ele');
    var submissionVoteCount = document.getElementById('vote-ele');
    var submissionViews = document.getElementById('view-ele');

    // Setting each element to the submission data

    authorElement.textContent = submission.user_name;
    titleElement.textContent = submission.title;
    descriptionElement.textContent = submission.description;

    mapElement.setAttribute("data-fancybox", submission.id);
    mapElement.setAttribute("href", submission.map_url);

    videoElement.setAttribute("data-fancybox", submission.id);
    videoElement.setAttribute("href", submission.video_link);

    thumbnailElement.setAttribute("data-fancybox", submission.id);
    thumbnailElement.setAttribute("href", submission.thumbnail_url);

    mapElement.innerHTML = `<img src="${submission.map_url}" alt="Map Preview" class="preview-image">`;
    videoElement.innerHTML = `<img src="${videoThumbnailLink}" alt="Video Preview" class="preview-image">`;
    thumbnailElement.innerHTML = `<img src="${submission.thumbnail_url}" alt="Thumbnail Preview" class="preview-image">`;

    submissionData.textContent = formattedDate;
    submissionVoteCount.textContent = submission.likes;
    submissionViews.textContent = submission.views;

    // check for extra images
    var extraImagesLabel = document.getElementById('media-content');
    var extraImages = document.getElementById('media-content-list');

    // check if there are extra images, if there are more than 2
    //console.log(submission.images.length);
    if (submission.images.length > 2) {
      //console.log('There are extra images');
      extraImagesLabel.classList.remove('hide');
      extraImages.classList.remove('hide');

      submission.images.forEach(function (image) {
        console.log(image)

        // check if the image is the thumbnail or map
        if (image.image_url == submission.thumbnail_url || image.image_url == submission.map_url) {
          return;
        }

        // create a div element
        var div = document.createElement('div');
        div.classList.add('media-item');
        div.setAttribute('data-fancybox', submission.id);
        div.setAttribute('href', image.image_url);

        // create an image element
        var img = document.createElement('img');
        img.src = image.image_url;
        img.alt = 'Extra Image';
        img.classList.add('preview-image');


        // append the image to the div
        div.appendChild(img);

        // append the div to the extra images list
        extraImagesLabel.appendChild(div);

      });
    }

    // if user is admin
    if (ifAdmin) {
      document.getElementsByClassName('admin-view')[0].classList.remove('hide');

      var statusElement = document.getElementsByClassName('sub-adstat')[0];
      var reasonElement = document.getElementsByClassName('admin-reason')[0];
      var reasonTextElement = document.getElementsByClassName('sub-adreason')[0];

      statusElement.textContent = submission.state;

      reasonElement.style.display = 'none';
      if (submission.is_rejected) {
        reasonTextElement.textContent = submission.rejected_reason;
        reasonElement.style.display = 'block';
      } else if (submission.is_removed) {
        reasonTextElement.textContent = submission.removed_reason;
        reasonElement.style.display = 'block';
      }

      // if state isn't rejected, hide the unreject button
      var unrejBtn = document.querySelector('.admin-btn.unreject');
      unrejBtn.setAttribute('data-id', submission.id);
      if (submission.state != 'Rejected') unrejBtn.classList.add('hide');

      // if state is approved, in progress, or rejected, hide the approve button
      var apvBtn = document.querySelector('.admin-btn.approve');
      apvBtn.setAttribute('data-id', submission.id);
      if (submission.state == 'Approved' || submission.state == 'In Progress' || submission.state == 'Rejected') apvBtn.classList.add('hide');

      // if state is rejected, hide the reject button
      var rejBtn = document.querySelector('.admin-btn.reject');
      rejBtn.setAttribute('data-id', submission.id);
      if (submission.state == 'Rejected') rejBtn.classList.add('hide');

      // if state is removed, hide the remove button
      var delBtn = document.querySelector('.admin-btn.delete');
      delBtn.setAttribute('data-id', submission.id);

      if (submission.state == 'Removed') {
        delBtn.classList.add('hide');
        apvBtn.classList.add('hide');
        rejBtn.classList.add('hide');
        unrejBtn.classList.add('hide');
      }
    }

    // if submission is approved, show share buttons
    if (submission.state == 'Approved') {
      document.getElementsByClassName('social-buttons')[0].classList.remove('hide');
      // set onclick for share buttons
      var shareBtns = document.getElementsByClassName('share-btn');
      for (var i = 0; i < shareBtns.length; i++) {
        shareBtns[i].setAttribute('onclick', `shareSubmissionInt('${shareBtns[i].getAttribute('data-platform')}')`);
      }

      // Show the vote button
      var voteBtn = document.getElementById('vote-btn');
      if (voteBtn) {
        voteBtn.classList.remove('hide');
        voteBtn.setAttribute('data-id', submission.id);
      }
    }


  } catch (error) {
    console.error('Error showing submission details:', error);
  }
}

function shareSubmissionInt(platform) {
  WLD_API.shareSubmission(platform, loadedSubmission);
}

const adminButtons = document.getElementById('admin-controls');

adminButtons.addEventListener('click', async function (event) {
  const target = event.target;
  if (target && target.matches('button.admin-btn')) {
    const button = target;
    const action = Array.from(button.classList).find(cls => ['approve', 'reject', 'delete', 'unreject'].includes(cls));

    adminAction(action, loadedSubmission.id, button);
  }
});

async function adminAction(action, id, button) {
  console.log('Admin action:', action, id);

  switch (action) {
    case 'approve':
      console.log("Got Approved")
      button.disabled = true;

      try {
        var admin_result = await PTA_API.admin_submission('approve', id);

        console.log("Admin Result:", admin_result);

        if (admin_result.code === 'success_action') {
          window.location.reload();
        }

      } catch (error) {
        return error;
      }

      break;

    case 'reject':
      console.log("Got Rejected");
      button.disabled = true;

      var reason = prompt("Please enter the reason for rejection:", "This Submission was rejected");
      if (reason === null || reason.trim() === "") {
        alert("Rejection message cannot be empty.");
        return;
      }

      try {
        var admin_result = await PTA_API.admin_submission('reject', id, reason);

        console.log("Admin Result:", admin_result);

        if (admin_result.code === 'success_action') {
          window.location.reload();
        }

      } catch (error) {
        return error;
      }

      break;

    case 'delete':
      console.log("Got Deleted");
      button.disabled = true;

      var reason = prompt("Please enter the reason for removal:", "This Submission was removed");
      if (reason === null || reason.trim() === "") {
        alert("Rejection message cannot be empty.");
        return;
      }

      try {
        var admin_result = await PTA_API.admin_submission('delete', id, reason);

        console.log("Admin Result:", admin_result);

        if (admin_result.code === 'success_action') {
          window.location.reload();
        }

      } catch (error) {
        return error;
      }

      break;

    case 'unreject':
      console.log("Got Unrejected");
      button.disabled = true;

      try {
        var admin_result = await PTA_API.admin_submission('unreject', id);

        console.log("Admin Result:", admin_result);

        if (admin_result.code === 'success_action') {
          window.location.reload();
        }

      } catch (error) {
        return error;
      }

      break;
    default:
      console.error("Invalid action:", action);
      break;
  }
};

function getYouTubeVideoID(url) {
  const regex = /(?:youtube\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
  const match = url.match(regex);
  return (match && match[1]) ? match[1] : null;
}

Fancybox.bind("[data-fancybox]", {
  protect: true
});

const voteButton = document.getElementById('vote-btn');
if (voteButton) {
  voteButton.addEventListener('click', async function () {
    console.log("Vote button clicked");
    const submissionID = voteButton.getAttribute('data-id');
    var responce = await PTA_API.voteSubmission(submissionID);
    if(responce.success == true){
      jQuery(document.body).trigger('added_to_cart', [responce.data.fragments, responce.data.cart_hash, jQuery(this)]);
      jQuery(document.body).trigger('wc_fragment_refresh');
    } else {
      console.error("Error:", responce.error);
      alert("Error adding vote to cart. Please try again later. If the problem persists, please contact the site administrator.");
    }
  });
} else {
  console.error("Vote button not found");
}