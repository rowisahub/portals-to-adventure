(function ($) {
    // Override the getApprovedSubmissions function
    PTA_API.getApprovedSubmissions = async function () {
        try {
            const data = await $.ajax({
                url: pta_api_data.api_url + `submissions?state=Approved?requested=${user_data.user_name}`,
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
                }
            });
            if(PTA_API.checkSubmissionResponse(data)) {
                return data.submissions;
            } else {
                throw new Error('Invalid response from API');
            }
        } catch (error) {
            console.error('Error fetching approved submissions:', error);
            throw error;
        }
    };
})(jQuery);