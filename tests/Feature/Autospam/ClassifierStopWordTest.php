<?php

use App\Util\Lexer\Classifier;

/*
|--------------------------------------------------------------------------
| Naive Bayes classifier stop-word symmetry
|--------------------------------------------------------------------------
|
| Stop-words (the, a, of, and) were dropped only from the spam lexicon during
| training and never at inference, so a spam caption padded with stop-words was
| pushed toward ham (spam used the Laplace floor for each stop-word while ham
| used its genuine stop-word frequency). Stop-words must be filtered uniformly
| in tokenize() for both classes and at classification time.
|
*/

function trainedClassifier(): Classifier
{
    $c = new Classifier;

    // Spam corpus: distinctive spam vocab.
    foreach (range(1, 40) as $i) {
        $c->learn('crypto giveaway click now', 'spam');
    }

    // Ham corpus, much larger, and full of stop-words.
    foreach (range(1, 200) as $i) {
        $c->learn('the photo of a sunset and the beach', 'ham');
    }

    return $c;
}

it('classifies a stop-word-padded spam caption as spam', function () {
    $c = trainedClassifier();

    expect($c->most('the a of and crypto giveaway'))->toBe('spam');
});

it('still classifies a genuine ham caption as ham', function () {
    $c = trainedClassifier();

    expect($c->most('the photo of a beach'))->toBe('ham');
});

it('drops stop-words from tokenization', function () {
    $c = new Classifier;

    $tokens = $c->tokenize('the a of and crypto giveaway')->all();

    expect($tokens)->toBe(['crypto', 'giveaway']);
});

it('keeps stop-words out of both lexicons after training', function () {
    $c = trainedClassifier();

    $export = json_decode($c->export(), true);

    foreach (['spam', 'ham'] as $class) {
        foreach (['the', 'a', 'of', 'and'] as $stop) {
            expect($export['words'][$class] ?? [])->not->toHaveKey($stop);
        }
    }
});
