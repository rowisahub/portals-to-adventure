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
            const data = await $.ajax({
                url: pta_api_data.apiv2_url + 'submission?status=approved',
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

    function checkSubmissionResponse(response) {
        // check if response has `submissions` and `errors` properties
        if (!response.hasOwnProperty('submissions') || !response.hasOwnProperty('errors')) {
            return false;
        }

        return true;
    }

    return {
        getSubmissions: getSubmissions,
        getSubmissionDetails: getSubmissionDetails,
        getUserSubmissions: getUserSubmissions,
        getApprovedSubmissions: getApprovedSubmissions
    };
  
})(jQuery);