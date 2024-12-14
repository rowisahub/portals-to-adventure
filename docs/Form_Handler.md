# Form Handlers Documentation

## handle_submission_upload

Handles the submission upload form submission.

### Endpoint

Form name `submission_upload_form`

### Parameters

- `title` (string, required): The title of the submission.
- `description` (string, required): A description of the submission.
- `video_link` (string, required): A link to a video related to the submission.
- `map` (file): An array of map images associated with the submission.
- `thumbnail` (file): An array of thumbnail images associated with the submission.

### Responses

- `200`: Submission uploaded successfully.
- `400`: Invalid input.
- `403`: Forbidden.

## handle_edit_form

Handles the submission edit form submission.

### Endpoint

Form name `submission_edit_form`

### Parameters

- `submission_id` (integer, required): The ID of the submission.
- `submission_title` (string, required): The title of the submission.
- `submission_description` (string, required): A description of the submission.
- `video_link` (string): A link to a video related to the submission.
- `remove_image` (string): A comma-separated list of image IDs to remove.
- `new_images` (array of files): An array of new images to upload.
- `set_thumbnail` (string): The ID or [filename+filesize], of the image to set as the thumbnail.
- `set_map` (string): The ID or [filename+filesize], of the image to set as the map.
- `update_submission` (string): The action to perform (e.g., "Save", "Publish", "Delete").

### Responses

- `200`: Submission edited successfully.
- `400`: Invalid input.
- `403`: Forbidden.