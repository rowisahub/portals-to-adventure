<?php
/**
 * @OA\PathItem(
 *   path="/submission",
 *   @OA\Get(
 *     summary="Retrieve submissions",
 *     description="Retrieve submission data based on optional filters: id, user_id, state, limit.",
 *     security={{"X-WP-Nonce":{}}},
 *     @OA\Parameter(
 *       name="id",
 *       in="query",
 *       description="Single or multiple submission IDs (comma-separated)",
 *       required=false,
 *       @OA\Schema(type="string", example="123,124")
 *     ),
 *     @OA\Parameter(
 *       name="user_id",
 *       in="query",
 *       description="Single or multiple user IDs (comma-separated)",
 *       required=false,
 *       @OA\Schema(type="string", example="10,11")
 *     ),
 *     @OA\Parameter(
 *       name="state",
 *       in="query",
 *       description="Filter submissions by state (e.g. 'Approved')",
 *       required=false,
 *       @OA\Schema(type="string", example="Approved")
 *     ),
 *     @OA\Parameter(
 *       name="limit",
 *       in="query",
 *       description="Limit the number of submissions returned",
 *       required=false,
 *       @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Response(
 *       response=200,
 *       description="A list of submissions and any errors.",
 *       @OA\JsonContent(
 *         @OA\Property(
 *           property="submissions", 
 *           type="array", 
 *           @OA\Items(ref="#/components/schemas/Submission")
 *         ),
 *         @OA\Property(
 *           property="errors", 
 *           type="array", 
 *           @OA\Items(
 *             @OA\Property(property="code", type="string"),
 *             @OA\Property(property="message", type="string")
 *           )
 *         )
 *       )
 *     ),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=403, description="Forbidden")
 *   ),
 *   @OA\Post(
 *     summary="Edit a submission",
 *     description="Edit submission data. Authentication required.",
 *     security={{"X-WP-Nonce":{}}},
 *     @OA\RequestBody(
 *       required=true,
 *       @OA\JsonContent(
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="state", type="string")
 *       )
 *     ),
 *     @OA\Response(response=200, description="Submission successfully edited"),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=403, description="Forbidden")
 *   )
 * )
