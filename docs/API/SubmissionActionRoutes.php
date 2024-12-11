<?php
/**
 * @OA\PathItem(
 *   path="/submission/action",
 *   @OA\Post(
 *     path="/submission/action",
 *     summary="Perform an action on one or more submissions",
 *     description="Actions: approve, reject, delete, unreject.",
 *     security={{"X-WP-Nonce":{}}},
 *     @OA\RequestBody(
 *       required=true,
 *       @OA\JsonContent(
 *         @OA\Property(property="action", type="string", enum={"approve","reject","delete","unreject"}),
 *         @OA\Property(property="id", type="string"),
 *         @OA\Property(property="reason", type="string", nullable=true)
 *       )
 *     ),
 *     @OA\Response(response=200, description="Action applied successfully"),
 *     @OA\Response(response=400, description="Invalid action or missing parameters"),
 *     @OA\Response(response=403, description="Forbidden"),
 *     @OA\Response(response=404, description="Submission not found")
 *   )
 * )
