var PTA_API = (function ($) {

    async function getSubmissions() {
        try {
            const data = await $.ajax({
                url: pta_api_data.apiv2_url + 'submission',
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', pta_api_data.nonce);
                }
            });
            return data;
        } catch (error) {
            console.error('Error fetching submissions:', error);
            throw error;
        }
    }

    function checkSubmissionResponse(response) {
        // che
    }

    //
  
})(jQuery);