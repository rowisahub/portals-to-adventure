var loadedSubmissions = null;

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
    var SelectList = document.getElementById('secret-doors-list');
    var submissions = await PTA_API.getUserSubmissions(pta_api_data.user_id);
    loadedSubmissions = submissions;
    console.log(submissions);

    var ifFoundSubmissions = false;
    submissions.forEach(function (submission) {

      // For This page we need to check if the submission is the user's submission and is 'Approved'
      if (submission.state !== 'Approved') {
        return;
      }
      ifFoundSubmissions = true;

      const date = new Date(submission.created_at.replace(' ', 'T'));
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      const formattedDate = date.toLocaleDateString('en-US', options);

      submission.created_at_formatted = formattedDate;

      var option = document.createElement('option');
      option.value = submission.id;
      option.text = submission.title + " (" + formattedDate + ")";
      SelectList.appendChild(option);
    });
    if (!ifFoundSubmissions) {
      // clear the list and put a message
      SelectList.innerHTML = '<option value="-1">No Approved submissions found</option>';
    }

    const urlParams = new URLSearchParams(window.location.search);
    console.log(urlParams);
    const submissionId = urlParams.get('submission_id');
    if (submissionId) {
      SelectList.value = submissionId;
      SelectList.dispatchEvent(new Event('change'));
    }

  } catch (error) {
    console.error('Error fetching submissions:', error);
    alert('Error fetching submissions. Please try again later.');
  }
});

document.getElementById("delete-btn").addEventListener("click", async function () {
  var selectedId = document.getElementById("secret-doors-list").value;
  console.log("Selected ID:", selectedId);

  //check if the selected submission is in the list
  if (selectedId == -1) {
    alert("No submission selected");
    return;
  }

  var ifconfirm = confirm("Are you sure you want to delete this submission?");
  console.log("Confirm:", ifconfirm);
  if (!ifconfirm) {
    return;
  }
  try {
    var response = await WLD_API.user_submission("delete", selectedId, "This Submission was removed by user");
    console.log(response);

    if (response.code === 'success_action') {
      console.log("Submission Deleted Successfully");
      alert("Submission Deleted Successfully");
      location.reload();
    }

  } catch (error) {
    console.error('Error deleting submission:', error);
    alert('Error deleting submission. Please try again later.');
  }
});

function getYouTubeVideoID(url) {
  const regex = /(?:youtube\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
  const match = url.match(regex);
  return (match && match[1]) ? match[1] : null;
}

document.getElementById("secret-doors-list").addEventListener("change", function () {
  //
  console.log("Loading submission details...");

  // Get the selected submission ID
  var selectedId = this.value;
  console.log("Selected ID:", selectedId);

  // Find the selected submission in the loaded submissions
  var selectedSubmission = loadedSubmissions.find(function (submission) {
    return submission.id == selectedId;
  });

  console.log("Selected Submission:", selectedSubmission);

  // Update the form fields with the selected submission details
  document.getElementById("title").innerText = "Title: " + selectedSubmission.title;
  document.getElementById("submission-date").innerHTML = "Date: " + selectedSubmission.created_at_formatted;
  document.getElementById("adventure-text").innerHTML = selectedSubmission.description;
  document.getElementById("vote-count").innerHTML = 'Votes: ' + selectedSubmission.likes;


  const videoID = getYouTubeVideoID(selectedSubmission.video_link);
  const videoThumbnailLink = `https://img.youtube.com/vi/${videoID}/hqdefault.jpg`;

  var mapPrv = document.getElementById("map-preview");
  var VideoPrv = document.getElementById("video-preview");
  var ThumbnailPrv = document.getElementById("thumbnail-preview");

  // add data-fancybox="${selectedSubmission.id}" to the anchor tags
  mapPrv.setAttribute("data-fancybox", selectedSubmission.id);
  VideoPrv.setAttribute("data-fancybox", selectedSubmission.id);
  ThumbnailPrv.setAttribute("data-fancybox", selectedSubmission.id);

  // set the href attribute to the thumbnail, video, and map
  mapPrv.setAttribute("href", selectedSubmission.map_url);
  VideoPrv.setAttribute("href", selectedSubmission.video_link);
  ThumbnailPrv.setAttribute("href", selectedSubmission.thumbnail_url);

  // make image inside respective div and add the src attribute
  // mapPrv.innerHTML = `<img src="${selectedSubmission.map_url}" alt="Map Image" class="preview-image">`;
  // VideoPrv.innerHTML = `<img src="${videoThumbnailLink}" alt="Video Image" class="preview-image">`;
  // ThumbnailPrv.innerHTML = `<img src="${selectedSubmission.thumbnail_url}" alt="Thumbnail Image" class="preview-image">`;
  mapPrv.querySelector('img').src = selectedSubmission.map_url;
  VideoPrv.querySelector('img').src = videoThumbnailLink;
  ThumbnailPrv.querySelector('img').src = selectedSubmission.thumbnail_url;

  // change data-id attribute of the share buttons
  var shareBtns = document.getElementsByClassName("social-buttons")[0];
  shareBtns.querySelectorAll('.share-btn').forEach(btn => {
    // set onclick attribute to the shareSubmission function
    btn.setAttribute("onclick", `shareSubmissionInt('${btn.dataset.platform}', '${selectedSubmission.id}')`);
  });

});


function shareSubmissionInt(platform, submissionID) {
  WLD_API.shareSubmission(platform, loadedSubmissions.find(submission => submission.id == submissionID));
}

Fancybox.bind("[data-fancybox]", {
  protect: true
});