var PTA_API = (function ($) {

    
    /**
     * Fetches submissions from the API.
     *
     * This function makes an asynchronous GET request to the API endpoint for submissions.
     * It includes a nonce in the request headers for authentication.
     *
     * @async
     * @function getSubmissions
     * @returns {Promise<Array>} A promise that resolves to an array of submissions if the response is valid.
     * @throws {Error} Throws an error if the API response is invalid or if there is an issue with the request.
     */
    async function getSubmissions() {
        try {

            const data = await $.ajax({
                url: pta_api_data.apiv2_url + 'submission',
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
                }
            });

            if(checkSubmissionResponse(data)) {
                return data.submissions;
            } else {
                throw new Error('Invalid response from API');
            }

        } catch (error) {
            console.error('Error fetching submissions:', error);
            throw error;
        }
    }

    /**
     * Fetches the details of a submission by its ID.
     *
     * @param {number} id - The ID of the submission to fetch.
     * @returns {Promise<Object>} A promise that resolves to the submission details.
     * @throws {Error} If there is an error fetching the submission details or the response is invalid.
     */
    async function getSubmissionDetails(id){
        try {
            const data = await $.ajax({
                url: pta_api_data.apiv2_url + 'submission?id=' + id,
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
                }
            });

            if(checkSubmissionResponse(data)) {
                return data.submissions[0];
            } else {
                throw new Error('Invalid response from API');
            }


        } catch (error) {
            console.error('Error fetching submission details:', error);
            throw error;
        }
    }

    /**
     * Fetches user submissions from the API.
     *
     * @param {number} userId - The ID of the user whose submissions are to be fetched.
     * @returns {Promise<Array>} A promise that resolves to an array of user submissions.
     * @throws {Error} Throws an error if the API response is invalid or if there is an issue with the request.
     */
    async function getUserSubmissions(userId){
        try {
            const data = await $.ajax({
                url: pta_api_data.apiv2_url + 'submission?user_id=' + userId,
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
                }
            });

            if(checkSubmissionResponse(data)) {
                return data.submissions;
            } else {
                throw new Error('Invalid response from API');
            }

        } catch (error) {
            console.error('Error fetching user submissions:', error);
            throw error;
        }
    }

    /**
     * Fetches approved submissions from the API.
     *
     * This function makes an asynchronous GET request to the API to retrieve submissions
     * with a status of 'approved'.
     *
     * @async
     * @function getApprovedSubmissions
     * @returns {Promise<Array>} A promise that resolves to an array of approved submissions.
     * @throws {Error} Throws an error if the API request fails or returns an invalid response.
     */
    async function getApprovedSubmissions() {
        try {

            var api_url = pta_api_data.apiv2_url + 'submission?state=approved';
            if(pta_api_data.user_admin){
                api_url += '&requested=' + user_data.user_name;
            }

            const data = await $.ajax({
                url: api_url,
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
                }
            });

            if(checkSubmissionResponse(data)) {
                return data.submissions;
            } else {
                throw new Error('Invalid response from API');
            }

        } catch (error) {
            console.error('Error fetching approved submissions:', error);
            throw error;
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
                url: pta_api_data.apiv2_url + 'submission/action',
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

    function checkSubmissionResponse(response) {
        // check if response has `submissions` and `errors` properties
        if (!response.hasOwnProperty('submissions') || !response.hasOwnProperty('errors')) {
            return false;
        }

        return true;
    }

    // const sseConnect = () => {
    //     if ('EventSource' in window) {

    //         // console.log('URL: ', ajax_object.ajax_url);

    //         // // remove protocol from URL
    //         // const url = ajax_object.ajax_url.replace(/^https?:\/\//, '');

    //         // console.log('URL2: ', url);

    //         // const sseURL = new URL(url);
    //         // sseURL.searchParams.append('action', 'wldpta_sse');
    //         // sseURL.searchParams.append('nonce', ajax_object.nonce);

    //         //const url = 'https://dev.portals-to-adventure.com/wp-content/plugins/portals-to-adventure/src/plugin/API/tsse.php';

    //         const url = `${ajax_object.ajax_url}?action=wldpta_sse&nonce=${ajax_object.nonce}`;

    //         const eventSource = new EventSource(
    //             url,
    //             { withCredentials: true }
    //         );

    //         console.log('Initial ReadyState:', eventSource.readyState);

    //         console.log('EventSource:', eventSource);

    //         const checkState = () => {
    //             switch(eventSource.readyState) {
    //                 case EventSource.CONNECTING: // 0
    //                     console.log('Connecting...');
    //                     break;
    //                 case EventSource.OPEN: // 1
    //                     console.log('Connection established');
    //                     break;
    //                 case EventSource.CLOSED: // 2
    //                     console.log('Connection closed');
    //                     break;
    //             }
    //         };
        
    //         eventSource.addEventListener('message', (event) => {
    //             const data = JSON.parse(event.data);
    //             console.log('Received2:', data);
    //             // Handle event data here
    //         });
        
    //         eventSource.addEventListener('error', (error) => {
    //             //console.error('SSE Error2:', error);
    //             if (error.target.readyState === EventSource.CLOSED) {
    //                 console.log('Connection closed');
    //                 // Close the connection
    //             } else {
    //                 console.error('SSE Error:', error.target);
    //             }
    //             eventSource.close();
    //         });
        
    //         eventSource.addEventListener('open', (event) => {
    //             console.log('SSE Connection opened:', event);
    //         });
            
    //         eventSource.addEventListener('close', (event) => {
    //             console.log('SSE Connection closed:', event);
    //         });

    //         eventSource.addEventListener('heartbeat', (event) => {
    //             console.log('Heartbeat:', event);
    //         });

    //         eventSource.addEventListener('connection', (event) => {
    //             console.log('Connection:', event);
    //         });

    //         // every 5 seconds, check the state of the connection
    //         setInterval(checkState, 5000);

    //         return eventSource;
    
    //     }
    // };

    // // Initialize SSE when document is ready
    // $(document).ready(() => {
    //     console.log('Document ready');
    //     sseConnect();
    // });

    return {
        getSubmissions: getSubmissions,
        getSubmissionDetails: getSubmissionDetails,
        getUserSubmissions: getUserSubmissions,
        getApprovedSubmissions: getApprovedSubmissions,
        checkSubmissionResponse: checkSubmissionResponse,
        admin_submission: admin_submission_action,
        shareSubmission: shareSubmission,
        voteSubmission: voteSubmission
    };
  
})(jQuery);

