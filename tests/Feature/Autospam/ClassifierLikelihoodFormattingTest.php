<?php

use App\Util\Lexer\Classifier;
use Brick\Math\BigDecimal;

/*
|--------------------------------------------------------------------------
| Naive Bayes classifier likelihood formatting
|--------------------------------------------------------------------------
|
| guess() formats each class likelihood with BigDecimal::of() before string
| sorting. brick/math 1.0 dropped float inputs from of(): a raw float is
| implicitly truncated to int, collapsing every fractional likelihood to "0"
| and destroying the ranking. The value must be stringified first so the full
| decimal survives. These tests pin that behaviour.
|
*/

function likelihoodClassifier(): Classifier
{
    $c = new Classifier;

    foreach (range(1, 40) as $i) {
        $c->learn('crypto giveaway click now', 'spam');
    }

    foreach (range(1, 200) as $i) {
        $c->learn('photo sunset beach morning', 'ham');
    }

    return $c;
}

it('formats fractional likelihoods as full decimal strings, not zero', function () {
    $scores = likelihoodClassifier()->guess('crypto giveaway click')->all();

    foreach ($scores as $class => $score) {
        expect($score)->toBeString();
        // A collapsed-to-int likelihood would surface as "0" for sub-1.0 probabilities.
        expect($score)->not->toBe('0');
        expect($score)->toMatch('/^0\.\d+$/');
    }
});

it('ranks the correct class after decimal formatting', function () {
    $c = likelihoodClassifier();

    expect($c->most('crypto giveaway click now'))->toBe('spam');
    expect($c->most('photo sunset beach morning'))->toBe('ham');
});

it('orders guess() results by descending likelihood string', function () {
    $scores = likelihoodClassifier()->guess('crypto giveaway click now')->values()->all();

    $sorted = $scores;
    rsort($sorted, SORT_STRING);

    expect($scores)->toBe($sorted);
});

it('preserves the full decimal when stringifying a fractional probability', function () {
    expect((string) BigDecimal::of((string) 0.5))->toBe('0.5');
    expect((string) BigDecimal::of((string) 0.123456789))->toBe('0.123456789');
    expect((string) BigDecimal::of((string) (0.1 * 0.1 * 0.1)))->toBe('0.001');
});
