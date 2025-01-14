const submissionList = document.getElementById("submission-list");

var loadedSubmissions = [];
var loadedSubmissionsView = [];

var ifAdmin = false;

// Search bar
const searchBar = document.getElementById('search-bar');
function searchSubmissions() {
  const query = searchBar.value.toLowerCase();

  loadedSubmissionsView.forEach(subJson => {
    const title = subJson.submission.title.toLowerCase();
    const user = subJson.submission.user_name.toLowerCase();
    const submissionID = subJson.submission.id;

    if (title.includes(query) || user.includes(query) || submissionID.includes(query)) {
      subJson.submissionDiv.style.display = 'block';
    } else {
      subJson.submissionDiv.style.display = 'none';
    }
  });
}


const filterDropdown = document.getElementById('admin-filter-dropdown');
filterDropdown.addEventListener('change', function () {
  const filter = this.value;

  console.log("Filtering by:", filter);

  loadedSubmissionsView.forEach(subJson => {
    const state = subJson.submission.state;

    if (filter === 'all' || state === filter) {
      subJson.submissionDiv.style.display = 'block';
    } else {
      subJson.submissionDiv.style.display = 'none';
    }
  });
});

function clearSubmissionList() {
  // Code to clear the submission list
  submissionList.innerHTML = '';
}


// on page load, get all submissions
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

  ifAdmin = pta_api_data.user_admin;
  getAllSubmissions();
});

//function to get all submissions
async function getAllSubmissions() {
  try {
    var submissions = await PTA_API.getApprovedSubmissions();

    console.log("Submissions:", submissions);

    if (ifAdmin) {
      // only show aprroved and pending approval submissions, and show pending approval submissions first
      submissions = submissions.filter(submission => submission.state === 'Approved' || submission.state === 'Pending Approval');
      submissions = submissions.sort((a, b) => {
        if (a.state === 'Pending Approval' && b.state === 'Approved') return -1;
        if (a.state === 'Approved' && b.state === 'Pending Approval') return 1;
        return 0;
      });

      document.getElementById('admin-filter-dropdown').classList.remove('hide');
    }

    loadedSubmissions = submissions;

    console.log(loadedSubmissions);
    // TODO
    // Add the submissions to the submission list
    var subLoaded = createSubmissionView();

    if (subLoaded){
      console.log("Submissions loaded");

      // run custom event when submission list is loaded
      var event = new Event('submissionsLoaded');
      document.dispatchEvent(event);
    } else {
      console.log("Submissions failed to load");
    }

  } catch (error) {
    console.error('Error fetching submissions:', error);
    alert('Error fetching submissions. Please try again later.');
  }
}


//function to create a new submission
function createSubmissionView() {
  //console.log("Creating new submission view");
  // Get The subm
  // if there are no submissions, show a message
  if (loadedSubmissions.length === 0) {
    submissionList.innerHTML = '<p>No submissions found</p>';
    return;
  }
  //console.log(loadedSubmissions);
  var ranSuccess = true;
  loadedSubmissions.forEach(submission => {
    try {
      // For This page we need to check if the submission is the user's submission and is 'Approved'
      if (submission.state == 'Removed') {
        return;
      }

      const date = new Date(submission.created_at.replace(' ', 'T'));
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      const formattedDate = date.toLocaleDateString('en-US', options);

      submission.created_at_formatted = formattedDate;

      renderSubmission(submission);

    } catch (error) {
      ranSuccess = false;
      console.error('Error loading submission(s):', error);
      alert('Error loading submissions. Please try again later.');
    }
  });

  if (ranSuccess) {
    return true;
  } else {
    return false;
  }
}

function getYouTubeVideoID(url) {
  const regex = /(?:youtube\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
  const match = url.match(regex);
  return (match && match[1]) ? match[1] : null;
}

function isYouTubeLink(url) {
  const regex = /^(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:\S+)?$/;
  return regex.test(url);
}

const subTemplate = document.getElementById('submission-template');

function renderSubmission(submission) {
  //console.log(" - Submission rendering");

  //console.log("  - Adding HTML");

  const submissionDiv = document.createElement('div');
  submissionDiv.classList.add('submission-view');
  submissionDiv.classList.add('pta-container');

  const submissionID = submission.id;

  if (submission.state == 'Pending Approval') {
    submissionDiv.style.border = '2px solid #f0ad4e';
  }

  var submissionClone = fillTemplate(submission);

  submissionDiv.appendChild(submissionClone);

  submissionList.appendChild(submissionDiv);

  const subTitle = document.getElementById(`sub-title-${submissionID}`);
  const subSubmitter = document.getElementById(`sub-submitter-${submissionID}`);
  const subVote = document.getElementById(`sub-vote-${submissionID}`);
  const subDescr = document.getElementById(`sub-descr-${submissionID}`);
  const subMap = document.getElementById(`sub-map-${submissionID}`);
  const subVideo = document.getElementById(`sub-video-${submissionID}`);
  const subThumbnail = document.getElementById(`sub-thumbnail-${submissionID}`);
  const subShareFb = document.getElementById(`sub-share-fb-${submissionID}`);
  const subShareX = document.getElementById(`sub-share-x-${submissionID}`);
  const subVoteBtn = document.getElementById(`sub-vote-${submissionID}`);

  const subJson = {
    id: submissionID,
    preview: {
      title: subTitle,
      submitter: subSubmitter,
      votes: subVote,
      description: subDescr,
      map: subMap,
      video: subVideo,
      thumbnail: subThumbnail,
      shareFb: subShareFb,
      shareX: subShareX,
      voteBtn: subVoteBtn
    },
    submissionDiv: submissionDiv,
    submission: submission
  }

  //window.location.href = `/submission?id=${submissionID}`;

  loadedSubmissionsView.push(subJson);

}

function fillTemplate(submission) {
  var submissionClone = subTemplate.content.cloneNode(true);

  var subtitle = submissionClone.querySelector('.sub-title');
  subtitle.textContent = submission.title;

  var subUser = submissionClone.querySelector('.sub-submitter');
  subUser.textContent = submission.user_name;

  var subVote = submissionClone.querySelector('.sub-vote');
  subVote.textContent = submission.likes;

  var subText = submissionClone.querySelector('.adventure-text');

  var viewMoreBtn = submissionClone.querySelector('.view-btn');

  var shareBtns = submissionClone.querySelector('.share-buttons');

  var voteBtn = submissionClone.querySelector('.vote-btn');
  var voteInst = submissionClone.querySelector('.vote-instructions');

  // set char limit and show View more button if char 
  var discriptionTextMax = 250;
  var descriptionLength = submission.description.length;
  var descriptionText = submission.description;

  subText.textContent = descriptionText;

  if (descriptionLength > discriptionTextMax) {
    // cut text off at nearest word
    descriptionText = descriptionText.substring(0, discriptionTextMax);
    descriptionText = descriptionText.substring(0, Math.min(descriptionText.length, descriptionText.lastIndexOf(" ")));
    descriptionText += '... ';
    subText.textContent = descriptionText;
    // <a href="/submission?id=${submission.id}">View More</a>
    subText.innerHTML += `<a href="/submission/?id=${submission.id}">View More</a>`;
  }

  // set the href attribute for view more button
  viewMoreBtn.setAttribute('href', `/submission/?id=${submission.id}`);

  const videoID = getYouTubeVideoID(submission.video_link) || '0';
  const videoThumbnailLink = `https://img.youtube.com/vi/${videoID}/hqdefault.jpg`;

  // Populate media items
  const mapItem = submissionClone.querySelector('.media-item.map');
  mapItem.setAttribute('href', submission.map_url);
  mapItem.setAttribute('data-fancybox', `submission-${submission.id}`);
  mapItem.querySelector('img').src = submission.map_url;

  const videoItem = submissionClone.querySelector('.media-item.video');
  videoItem.setAttribute('href', submission.video_link);
  videoItem.setAttribute('data-fancybox', `submission-${submission.id}`);
  videoItem.querySelector('img').src = videoThumbnailLink;

  const thumbnailItem = submissionClone.querySelector('.media-item.thumbnail');
  thumbnailItem.setAttribute('href', submission.thumbnail_url);
  thumbnailItem.setAttribute('data-fancybox', `submission-${submission.id}`);
  thumbnailItem.querySelector('img').src = submission.thumbnail_url;

  if (submission.state == 'Approved') {
    // Set button data attributes
    submissionClone.querySelectorAll('.share-btn').forEach(btn => {
      btn.onclick = function () {
        PTA_API.shareSubmission(btn.getAttribute('data-platform'), submission);
      }
    });
  } else {
    shareBtns.classList.add('hide');

    voteBtn.classList.add('hide');
    voteInst.classList.add('hide');
  }

  // Set vote button data attributes
  //submissionClone.querySelector('.add-to-cart-vote').setAttribute('data-product-id', pta_api_data.woocommerce_product_id);

  var voteSubBtn = submissionClone.querySelector('.vote-btn');
  voteSubBtn.setAttribute('data-id', submission.id);

  voteSubBtn.onclick = function () {
    PTA_API.voteSubmission(submission.id, voteSubBtn);
  }

  submissionClone.querySelector('.admin-bar-pta').style.display = ifAdmin ? 'block' : 'none';

  // admin state
  var adminState = submissionClone.querySelector('.sub-adstat');
  adminState.textContent = submission.state;

  // admin reason
  var adminReason = submissionClone.querySelector('.admin-reason');
  var adminReasonTxt = submissionClone.querySelector('.sub-adreason');
  if (ifAdmin) {
    adminReason.style.display = 'none';
    if (submission.is_rejected) {
      adminReasonTxt.textContent = submission.rejected_reason;
      adminReason.style.display = 'block';
    } else if (submission.is_removed) {
      adminReasonTxt.textContent = submission.removed_reason;
      adminReason.style.display = 'block';
    }
  }

  // unreject button
  var unrejBtn = submissionClone.querySelector('.admin-btn.unreject');
  unrejBtn.setAttribute('data-id', submission.id);
  if (submission.state != 'Rejected') unrejBtn.classList.add('hide');

  // check for submission state
  var apvBtn = submissionClone.querySelector('.admin-btn.approve');
  apvBtn.setAttribute('data-id', submission.id);
  if (submission.state == 'Approved' || submission.state == 'In Progress' || submission.state == 'Rejected') apvBtn.classList.add('hide');

  var rejBtn = submissionClone.querySelector('.admin-btn.reject');
  rejBtn.setAttribute('data-id', submission.id);
  if (submission.state == 'Rejected') rejBtn.classList.add('hide');

  var delBtn = submissionClone.querySelector('.admin-btn.delete');
  delBtn.setAttribute('data-id', submission.id);

  return submissionClone;
}

document.addEventListener('submissionsLoaded', async function () {
  var voteBtns = document.querySelectorAll('.vote-btn');

  voteBtns.forEach(voteBtn => {

    voteBtn.onclick = async function () {
      console.log("Vote button clicked");

      const submissionID = voteBtn.getAttribute('data-id');
      //const productID = voteBtn.getAttribute('data-product-id');
      
      var responce = await PTA_API.voteSubmission(submissionID);

      console.log("Vote responce:", responce);

      if(responce.success == true){
        jQuery(document.body).trigger('added_to_cart', [responce.data.fragments, responce.data.cart_hash, jQuery(this)]);
        jQuery(document.body).trigger('wc_fragment_refresh');
      } else {
        console.error("Error:", responce.error);
        alert("Error adding vote to cart. Please try again later. If the problem persists, please contact the site administrator.");
      }

    }
  });
});

const submissionsContainer = document.getElementById('submission-list');

submissionsContainer.addEventListener('click', function (event) {
  // Check if the clicked element is an admin button
  if (event.target && event.target.matches('button.admin-btn')) {
    const button = event.target;
    const action = Array.from(button.classList).find(cls => ['approve', 'reject', 'delete', 'unreject'].includes(cls));
    const submissionId = button.getAttribute('data-id');

    adminAction(action, submissionId, button);
  }
});

async function adminAction(action, submissionID, button) {
  console.log("Admin action:", action, "for submission:", submissionID);

  switch (action) {
    case 'approve':
      console.log("Got Approved")
      button.disabled = true;

      try {
        var admin_result = await PTA_API.admin_submission('approve', submissionID);

        console.log("Admin Result:", admin_result);

        if (admin_result.code === 'success_action') {
          resetSubmissionList();
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
        var admin_result = await PTA_API.admin_submission('reject', submissionID, reason);

        console.log("Admin Result:", admin_result);

        if (admin_result.code === 'success_action') {
          resetSubmissionList();
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
        var admin_result = await PTA_API.admin_submission('delete', submissionID, reason);

        console.log("Admin Result:", admin_result);

        if (admin_result.code === 'success_action') {
          resetSubmissionList();
        }

      } catch (error) {
        return error;
      }

      break;

    case 'unreject':
      console.log("Got Unrejected");
      button.disabled = true;

      try {
        var admin_result = await PTA_API.admin_submission('unreject', submissionID);

        console.log("Admin Result:", admin_result);

        if (admin_result.code === 'success_action') {
          resetSubmissionList();
        }

      } catch (error) {
        return error;
      }

      break;
    default:
      console.error("Invalid action:", action);
      break;
  }

}

// function to clear all submissions
function resetSubmissionList() {
  submissionList.innerHTML = '';
  loadedSubmissions = [];
  loadedSubmissionsView = [];

  console.log("Submission list cleared");

  getAllSubmissions();

  console.log("Submission list reloaded");
}

Fancybox.bind("[data-fancybox]", {
  protect: true
});
