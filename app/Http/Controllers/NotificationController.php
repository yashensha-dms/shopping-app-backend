<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquents\NotificationRepository;

class NotificationController extends Controller
{
    protected $repository;

    public function __construct(NotificationRepository $repository){
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/notification",
     *      operationId="getNotifications",
     *      tags={"Notifications"},
     *      summary="Get user notifications",
     *      description="Returns paginated list of notifications for the authenticated user.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="string", format="uuid"),
     *                      @OA\Property(property="type", type="string", example="App\\Notifications\\OrderPlaced"),
     *                      @OA\Property(property="data", type="object",
     *                          @OA\Property(property="message", type="string", example="Your order #1234 has been placed"),
     *                          @OA\Property(property="order_id", type="integer")
     *                      ),
     *                      @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
     *                      @OA\Property(property="created_at", type="string", format="date-time")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $user = $this->repository->findOrFail(Helpers::getCurrentUserId());
        return $user->notifications()->latest('created_at')->paginate($request->paginate ?? $user->count());
    }

    /**
     * @OA\Put(
     *      path="/notification/markAsRead",
     *      operationId="markNotificationAsRead",
     *      tags={"Notifications"},
     *      summary="Mark notifications as read",
     *      description="Mark one or all notifications as read.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="string", format="uuid", description="Notification ID (omit to mark all as read)")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Notification(s) marked as read")
     * )
     */
    public function markAsRead(Request $request)
    {
        return $this->repository->markAsRead($request);
    }

    /**
     * @OA\Delete(
     *      path="/notification/{id}",
     *      operationId="deleteNotification",
     *      tags={"Notifications"},
     *      summary="Delete notification",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *      @OA\Response(response=200, description="Notification deleted")
     * )
     */
    public function destroy(Request $request)
    {
        return $this->repository->destroy($request->id);
    }
}
