<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model status.
     */
    public function updateStatus(User $user, ServiceRequest $serviceRequest, string $newStatus): bool
    {
        // 1. Acceptance/Reject logic
        if (in_array($newStatus, ['accepted', 'rejected'])) {
            // If it's an application, only the offer owner (client) can accept/reject
            if ($serviceRequest->created_by_id === $serviceRequest->user_id) {
                return $user->id === $serviceRequest->serviceOffer->user_id;
            }
            // If it's an invitation, only the invited provider can accept/reject
            return $user->id === $serviceRequest->user_id;
        }

        // 2. Cancellation logic
        if ($newStatus === 'cancelled') {
            // Can cancel if you created the request or you are the one it's assigned to
            return $user->id === $serviceRequest->created_by_id || $user->id === $serviceRequest->user_id;
        }

        // 3. Completion logic
        if ($newStatus === 'completed') {
            // Must be already accepted to complete
            if ($serviceRequest->status !== 'accepted') return false;
            // Both parties can mark as completed
            return $user->id === $serviceRequest->serviceOffer->user_id || $user->id === $serviceRequest->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }
}
