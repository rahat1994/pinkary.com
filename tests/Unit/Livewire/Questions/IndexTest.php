<?php

declare(strict_types=1);

use App\Livewire\Questions\Index;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('render', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(Index::class, [
        'userId' => $user->id,
    ]);

    $component->assertStatus(200);
});

test('render with wrong user id', function () {
    $component = Livewire::test(Index::class, [
        'userId' => 123,
    ]);
})->throws(ModelNotFoundException::class);

test('only renders questions with answers if user is not auth user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $questions = Question::factory(3)->create([
        'from_id' => $userA->id,
        'to_id' => $userB->id,
    ]);

    $component = Livewire::actingAs($userA)->test(Index::class, [
        'userId' => $userB->id,
    ]);

    foreach ($questions as $question) {
        $component->assertSee($question->content);
    }

    $question = Question::factory()->create([
        'from_id' => $userA->id,
        'to_id' => $userB->id,
        'answer' => null,
        'answered_at' => null,
    ]);

    $component->dispatch('question.created');

    $component->assertDontSee($question->content);

    $question->update([
        'answer' => 'Hello World',
        'answered_at' => now(),
    ]);

    $component->dispatch('question.updated');

    $component->assertSee($question->content);
});

test('do not render reported questions', function () {
    $user = User::factory()->create([]);

    $questions = Question::factory(3)->create([
        'from_id' => $user->id,
        'is_reported' => true,
    ]);

    $component = Livewire::actingAs($user)->test(Index::class, [
        'userId' => $user->id,
    ]);

    foreach ($questions as $question) {
        $component->assertDontSee($question->content);
    }
});

test('destroy', function () {
    $user = User::factory()->create();

    $question = Question::factory()->create([
        'to_id' => $user->id,
    ]);

    $component = Livewire::actingAs($user)->test(Index::class, [
        'userId' => $user->id,
    ]);

    $component->assertSee($question->content);

    $component->dispatch('question.destroy', $question->id);

    $component->assertDontSee($question->content);

    expect($question->fresh())->toBeNull();
});

test('destroy auth', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $question = Question::factory()->create([
        'from_id' => $userA->id,
        'to_id' => $userB->id,
    ]);

    $component = Livewire::actingAs($userA)->test(Index::class, [
        'userId' => $userB->id,
    ]);

    $component->dispatch('question.destroy', $question->id);

    $component->assertStatus(403);

    expect($question->fresh())->not->toBeNull();
});

test('load more', function () {
    $user = User::factory()->create();

    $questions = Question::factory(120)->create([
        'to_id' => $user->id,
    ]);

    $component = Livewire::actingAs($user)->test(Index::class, [
        'userId' => $user->id,
    ]);

    $component->call('loadMore');
    $component->assertSet('perPage', 10);

    $component->call('loadMore');
    $component->assertSet('perPage', 15);

    foreach (range(1, 25) as $i) {
        $component->call('loadMore');
    }

    $component->assertSet('perPage', 100);
});