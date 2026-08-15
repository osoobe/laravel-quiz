<?php

namespace Osoobe\Quiz\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

abstract class QuizException extends RuntimeException
{
    protected string $errorCode = 'quiz.error';

    protected int $status = 403;

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], $this->status);
    }
}
