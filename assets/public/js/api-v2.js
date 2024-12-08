var PTA_API = (function ($) {

    // Get 
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

    function checkSubmissionResponse(response) {
        // check if response has `submissions` and `errors` properties
        if (!response.hasOwnProperty('submissions') || !response.hasOwnProperty('errors')) {
            return false;
        }

        return true;
    }

    return {
        getSubmissions: getSubmissions
    };
  
})(jQuery);