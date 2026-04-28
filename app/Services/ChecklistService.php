<?php

namespace App\Services;

use App\Models\Question;
use App\Models\TaskStep;
use App\Models\User;
use App\Repositories\Contracts\TaskStepRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChecklistService
{
    public function __construct(
        private readonly TaskStepRepositoryInterface $taskStepRepository
    ) {
    }

    /**
     * @param array<int, array{question_id:int, answer:bool, remark:?string}> $answers
     */
    public function submit(TaskStep $taskStep, array $answers, User $actor): TaskStep
    {
        return DB::transaction(function () use ($taskStep, $answers, $actor): TaskStep {
            $stepQuestionIds = $taskStep->workflowStep
                ->questionSets()
                ->with('questions:id,question_set_id,remarks_required_on_no')
                ->get()
                ->flatMap(fn ($set): Collection => $set->questions)
                ->pluck('id')
                ->values();

            if ($stepQuestionIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'questions' => 'No questions configured for this workflow step.',
                ]);
            }

            $answersByQuestion = collect($answers)->keyBy('question_id');

            foreach ($stepQuestionIds as $questionId) {
                if (!$answersByQuestion->has($questionId)) {
                    throw ValidationException::withMessages([
                        'answers' => "Missing answer for question {$questionId}.",
                    ]);
                }
            }

            $questions = Question::query()->whereIn('id', $stepQuestionIds)->get()->keyBy('id');

            foreach ($answers as $payload) {
                $question = $questions->get($payload['question_id']);
                if (!$question) {
                    throw ValidationException::withMessages([
                        'answers' => "Question {$payload['question_id']} does not belong to this step.",
                    ]);
                }

                $answer = (bool) ($payload['answer'] ?? false);
                $remark = $payload['remark'] ?? null;

                if (!$answer && $question->remarks_required_on_no && blank($remark)) {
                    throw ValidationException::withMessages([
                        'remark' => "Remark is required when answer is NO for question {$question->id}.",
                    ]);
                }

                $this->taskStepRepository->upsertAnswer($taskStep, $question->id, [
                    'answer' => $answer,
                    'remark' => $remark,
                    'submitted_by' => $actor->id,
                ]);
            }

            return $taskStep->refresh();
        });
    }
}
