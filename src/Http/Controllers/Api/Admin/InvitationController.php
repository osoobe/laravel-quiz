<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Osoobe\Quiz\Actions\InviteUsers;
use Osoobe\Quiz\Exceptions\QuizAccessDeniedException;
use Osoobe\Quiz\Http\Requests\InviteUsersRequest;
use Osoobe\Quiz\Http\Resources\InvitationResource;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizInvitation;
use Osoobe\Quiz\Services\QuizAccess;

/**
 * Reachable by staff-invitation-managers OR the quiz's own creator — same
 * per-object pattern as QuizCrudController, not blanket staff middleware.
 */
class InvitationController
{
    public function __construct(private QuizAccess $access)
    {
    }

    public function index(Request $request, Quiz $quiz)
    {
        $this->assertManager($request, $quiz);

        return InvitationResource::collection($quiz->invitations()->with('user')->latest()->get());
    }

    public function store(InviteUsersRequest $request, Quiz $quiz, InviteUsers $invite)
    {
        $this->assertManager($request, $quiz);

        return response()->json($invite->execute($quiz, $request->normalizedIdentifiers(), $request->user()->getKey()));
    }

    public function destroy(Request $request, Quiz $quiz, QuizInvitation $invitation)
    {
        $this->assertManager($request, $quiz);

        $invitation->delete();

        return response()->json(['message' => 'Invitation removed.']);
    }

    private function assertManager(Request $request, Quiz $quiz): void
    {
        $user = $request->user();

        if (! $user || (! $this->access->isInvitationManager($user) && $quiz->created_by !== (string) $user->getKey())) {
            throw new QuizAccessDeniedException('You may not manage invitations for this quiz.');
        }
    }
}
