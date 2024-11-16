var WLD_API = (function ($) {

  /**
   * Fetches submissions from the PTA API.
   *
   * This function sends an AJAX GET request to the PTA API to retrieve submissions.
   *
   * @async
   * @returns {Promise<Object>} A promise that resolves to the data retrieved from the API.
   * @throws {Error} Throws an error if the request fails.
   */
  async function getSubmissions() {
    try {
      const data = await $.ajax({
        url: pta_api_data.api_url + 'submissions',
        method: 'GET',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
        }
      });
      return data; // Return the data to be used with await
    } catch (error) {
      console.error('Error fetching submissions:', error);
      throw error; // Re-throw the error to be caught in the calling function
    }
  }


  /**
   * Fetches the details of a submission by its ID.
   *
   * @async
   * @param {uuidv4} id - The ID of the submission to fetch.
   * @returns {Promise<Object|null>} A promise that resolves to the submission details object if found, or null if not found.
   * @throws Will throw an error if the request fails.
   */
  async function getSubmissionDetails(id) {
    try {
      const data = await $.ajax({
        url: pta_api_data.api_url + 'submissions?id=' + id,
        method: 'GET',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
        }
      });
      if (data.length > 0) {
        return data[0];
      } else {
        return null;
      }
    } catch (error) {
      console.error('Error fetching submission details:', error);
      throw error;
    }
  }


  /**
   * Fetches user submissions from the API.
   *
   * @async
   * @param {number} userId - The ID of the user whose submissions are to be fetched.
   * @returns {Promise<Object>} A promise that resolves to the data of user submissions.
   * @throws Will throw an error if the request fails.
   */
  async function getUserSubmissions(userId) {
    try {
      const data = await $.ajax({
        url: pta_api_data.api_url + 'submissions?user_id=' + userId,
        method: 'GET',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
        }
      });
      return data;
    } catch (error) {
      console.error('Error fetching user submissions:', error);
      throw error;
    }
  }


  /**
   * Fetches submissions and filters them to return only the approved ones.
   *
   * @async
   * @returns {Promise<Array>} A promise that resolves to an array of approved submissions.
   * @throws Will throw an error if fetching submissions fails.
   */
  async function getApprovedSubmissions() {
    try {

      const data = await $.ajax({
        url: pta_api_data.api_url + 'submissions?state=Approved',
        method: 'GET',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
        }
      });
      return data;

    } catch (error) {
      console.error('Error fetching approved submissions:', error);
      throw error;
    }
  }

  async function getAllAdminSubmissions() {
    try {
      const data = await $.ajax({
        url: pta_api_data.api_url + 'submissions' + `?requested=${user_data.user_name}`,
        method: 'GET',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
        }
      });
      return data;
    } catch (error) {
      console.error('Error fetching admin submissions:', error);
      throw error
    }
  }


  /**
   * Sends an admin submission request to the server.
   *
   * @param {string} action - The action to be performed.
   * @param {number} id - The ID associated with the action.
   * @param {string} reason - Optional. The reason for the action.
   * @returns {Promise<void>} - A promise that resolves when the request is complete.
   * @throws {Error} - Throws an error if the request fails.
   */
  async function admin_submission_action(action, id, reason = 'Change requested by admin') {
    try {
      const data = await $.ajax({
        url: pta_api_data.api_url + 'submission-action',
        method: 'POST',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
        },
        data: {
          action: action,
          id: id,
          reason: reason
        }
      });
      return data;
    } catch (error) {
      console.error('Error performing admin submission action:', error);
      console.error('Error responce:', error.responseJSON);
      throw error;
    }
  }

  async function user_submission_action(action, id, reason = 'Change requested by user') {
    try {
      const data = await $.ajax({
        url: pta_api_data.api_url + 'submission-action',
        method: 'POST',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
        },
        data: {
          action: action,
          id: id,
          reason: reason
        }
      });
      return data;
    } catch (error) {
      console.error('Error performing user submission action:', error);
      console.error('Error responce:', error.responseJSON);
      throw error;
    }
  }

  /**
   * Shares a submission on a specified social media platform. Opens a new window to share the submission.
   *
   * @param {string} platform - The social media platform to share the submission on. Valid values are 'facebook' and 'twitter'.
   * @param {Object} submission - The submission object containing details to be shared.
   */
  function shareSubmission(platform, submission) {
    console.log("Sharing submission:", submission.id, "on platform:", platform);

    const shareURL = window.location.origin + `/submission?id=${submission.id}`;
    const shareText = "Check out this Adventure on Portal to Adventure: " + submission.title;

    switch (platform) {
      case 'facebook':
        const fbShareURL = `https://www.facebook.com/sharer/sharer.php?u=${shareURL}`;
        window.open(fbShareURL, '_blank');
        break;
      case 'twitter':
        const twShareURL = `https://twitter.com/intent/tweet?url=${shareURL}&text=${shareText}`;
        window.open(twShareURL, '_blank');
        break;
      default:
        console.error("Invalid platform:", platform);
        break;
    }
  }

  async function voteSubmission(submissionId) {
    try{
      const data = await $.ajax({
        url: ajax_object.ajax_url,
        method: 'POST',
        data: {
          action: 'wldpta_vote_add_to_cart',
          //product_id: productId,
          nonce: ajax_object.nonce,
          submission_id: submissionId
        }
      });
      return data;
    } catch (error) {
      console.error('Error voting submission:', error);
      throw error;
    }
  }

  return {
    getSubmissions: getSubmissions,
    getSubmissionDetails: getSubmissionDetails,
    getUserSubmissions: getUserSubmissions,
    getApprovedSubmissions: getApprovedSubmissions,
    getAllSubmissions: getAllAdminSubmissions,
    admin_submission: admin_submission_action,
    user_submission: user_submission_action,
    shareSubmission: shareSubmission,
    voteSubmission: voteSubmission
  };

})(jQuery);