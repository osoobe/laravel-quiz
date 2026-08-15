<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Osoobe\Quiz\Http\Requests\StoreTopicRequest;
use Osoobe\Quiz\Http\Requests\UpdateTopicRequest;
use Osoobe\Quiz\Http\Resources\TopicResource;
use Osoobe\Quiz\Models\QuizTopic;

class TopicCrudController
{
    public function index()
    {
        return TopicResource::collection(QuizTopic::query()->latest()->paginate(50));
    }

    public function store(StoreTopicRequest $request)
    {
        return new TopicResource(QuizTopic::create($request->validated()));
    }

    public function show(QuizTopic $topic)
    {
        return new TopicResource($topic);
    }

    public function update(UpdateTopicRequest $request, QuizTopic $topic)
    {
        $topic->update($request->validated());

        return new TopicResource($topic);
    }

    public function destroy(QuizTopic $topic)
    {
        $topic->delete();

        return response()->json(['message' => 'Topic deleted.']);
    }
}
