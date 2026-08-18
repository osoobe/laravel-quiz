<?php

namespace Osoobe\Quiz\Policies;

use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Services\QuizAccess;

class QuizCategoryPolicy
{
    public function __construct(private QuizAccess $access) {}

    public function viewAny(?QuizUser $user): bool
    {
        return true;
    }

    public function view(?QuizUser $user, QuizCategory $category): bool
    {
        return $category->is_active || ($user && $this->access->isStaff($user));
    }

    public function create(QuizUser $user): bool
    {
        return $this->access->isStaff($user);
    }

    public function update(QuizUser $user, QuizCategory $category): bool
    {
        return $this->access->isStaff($user);
    }

    public function delete(QuizUser $user, QuizCategory $category): bool
    {
        return $this->access->isStaff($user);
    }
}
