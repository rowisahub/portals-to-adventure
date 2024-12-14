# RESTv2 API Documentation

I have a web based version for the API documentation.
https://dev.portals-to-adventure.com/api-documentation/

## Endpoints

### GET /submission

Retrieve submission data based on optional filters: id, user_id, state, limit.

#### Parameters

- `id` (string, optional): Single or multiple submission IDs (comma-separated). Example: "123,124".
- `user_id` (string, optional): Single or multiple user IDs (comma-separated). Example: "10,11".
- `state` (string, optional): Filter submissions by state (e.g., "Approved"). Example: "Approved".
- `limit` (integer, optional): Limit the number of submissions returned. Example: 10.

#### Responses

- `200`: A list of submissions and any errors.
- `400`: Bad request.
- `403`: Forbidden.

### POST /submission/action

Perform an action on one or more submissions.

#### Parameters

- `action` (string, required): The action to perform. Possible values: "approve", "reject", "delete", "unreject".
- `id` (string, required): Single or multiple submission IDs (comma-separated).
- `reason` (string, optional): The reason for the action.

#### Responses

- `200`: Action applied successfully.
- `400`: Invalid action or missing parameters.
- `403`: Forbidden.
- `404`: Submission not found.

## Security

All endpoints require the `X-WP-Nonce` header for authentication.

### Example

```http
GET /wp-json/pta/v2/submission?&state=Approved&limit=10 HTTP/1.1
Host: dev.portals-to-adventure.com
X-WP-Nonce: your_nonce_here
Content-Type: application/json
```
Will return something like:
```json
{
    "submissions": [
        {
            "id": 123,
            "user_id": 10,
            "state": "Approved",
            "title": "Submission Title",
            "description": "Submission Description",
            "video_link": "https://www.youtube.com/watch?v=video_id",
            "map": "https://www.example.com/map.jpg",
            "thumbnail": "https://www.example.com/thumbnail.jpg",
            "images": [
                "https://www.example.com/image1.jpg",
                "https://www.example.com/image2.jpg"
            ]
        },
    ],
    "errors": []
}
```