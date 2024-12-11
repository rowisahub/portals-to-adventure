<?php
/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="PTA V2 API",
 *     version="2.0.0",
 *     description="API for handling submissions in PortalsToAdventure V2"
 *   ),
 *   @OA\Server(
 *     url="https://example.com/wp-json/pta/v2",
 *     description="Production API server"
 *   ),
 *   @OA\SecurityScheme(
 *     securityScheme="X-WP-Nonce",
 *     type="apiKey",
 *     in="header",
 *     name="X-WP-Nonce",
 *     description="WordPress REST API Nonce for authenticated requests"
 *   ),
 *   @OA\Components(
 *     @OA\Schema(
 *       schema="Submission",
 *       type="object",
 *       description="A single submission object",
 *       @OA\Property(property="id", type="integer", description="The unique submission ID"),
 *       @OA\Property(property="title", type="string", description="The title of the submission"),
 *       @OA\Property(property="description", type="string", description="A description of the submission"),
 *       @OA\Property(property="video_link", type="string", nullable=true, description="A link to a video related to the submission"),
 *       @OA\Property(property="state", type="string", description="The current state of the submission (e.g., 'Approved')"),
 *       @OA\Property(property="views", type="integer", description="Number of times this submission has been viewed"),
 *       @OA\Property(property="likes", type="integer", description="Number of 'likes' for this submission"),
 *       @OA\Property(property="user_id", type="integer", description="The ID of the user who owns this submission"),
 *       @OA\Property(property="user_name", type="string", description="The username of the owner"),
 *       @OA\Property(property="created_at", type="string", format="date-time", description="When the submission was created"),
 *       @OA\Property(
 *         property="images",
 *         type="array",
 *         description="An array of images associated with this submission",
 *         @OA\Items(
 *           type="object",
 *           @OA\Property(property="id", type="integer", description="Image ID"),
 *           @OA\Property(property="image_url", type="string", description="URL of the image"),
 *           @OA\Property(property="is_thumbnail", type="boolean", description="Whether this image is the thumbnail"),
 *           @OA\Property(property="is_map", type="boolean", description="Whether this image is a map image")
 *         )
 *       ),
 *       @OA\Property(property="thumbnail_url", type="string", nullable=true, description="URL to the thumbnail image"),
 *       @OA\Property(property="map_url", type="string", nullable=true, description="URL to the map image"),
 *       @OA\Property(property="removed_reason", type="string", nullable=true, description="Reason the submission was removed, if applicable"),
 *       @OA\Property(property="is_removed", type="boolean", nullable=true, description="Whether the submission is removed"),
 *       @OA\Property(property="is_rejected", type="boolean", nullable=true, description="Whether the submission is rejected"),
 *       @OA\Property(property="rejected_reason", type="string", nullable=true, description="The reason the submission was rejected"),
 *       @OA\Property(property="was_rejected", type="boolean", nullable=true, description="Whether the submission was rejected in the past")
 *     )
 *   )
 * )
 */