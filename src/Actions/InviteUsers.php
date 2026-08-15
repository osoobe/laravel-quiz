<?php

namespace Osoobe\Quiz\Actions;

use Illuminate\Database\QueryException;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizInvitation;

class InviteUsers
{
    /**
     * @param  array<int, string>  $identifiers  email addresses or @usernames
     * @return array{invited: int, already_invited: int, not_found: int, failed: int}
     */
    public function execute(Quiz $quiz, array $identifiers, string $invitedBy): array
    {
        $userModel = config('quiz.user_model');
        $result = ['invited' => 0, 'already_invited' => 0, 'not_found' => 0, 'failed' => 0];

        foreach ($identifiers as $identifier) {
            $identifier = trim($identifier);

            if ($identifier === '') {
                continue;
            }

            $user = str_starts_with($identifier, '@')
                ? $userModel::where('username', ltrim($identifier, '@'))->first()
                : $userModel::where('email', $identifier)->first();

            if (! $user) {
                $result['not_found']++;

                continue;
            }

            try {
                QuizInvitation::create([
                    'quiz_id' => $quiz->id,
                    'user_id' => $user->getKey(),
                    'invited_by' => $invitedBy,
                ]);
                $result['invited']++;
            } catch (QueryException $e) {
                if ((int) $e->getCode() === 23000) {
                    $result['already_invited']++;
                } else {
                    $result['failed']++;
                }
            }
        }

        return $result;
    }
}
