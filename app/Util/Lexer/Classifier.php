<?php

namespace App\Util\Lexer;

use Brick\Math\BigDecimal;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Classifier
{
	/**
	 * @var ?callable(string): array<int, string>
	 */
	private $tokenizer;

	/**
	 * @var array<string, array<string, int>>
	 */
	private array $words = [];

	/**
	 * @var array<string, int>
	 */
	private array $documents = [];

	private bool $uneven = false;

	/**
	 * @param callable(string): array<int, string> $tokenizer
	 */
	public function setTokenizer(callable $tokenizer): void
	{
		$this->tokenizer = $tokenizer;
	}

	/**
	 * @return Collection<int, string>
	 */
	public function tokenize(string $string): Collection
	{
		if ($this->tokenizer) {
			/** @var array<int, string> */
			$tokens = call_user_func($this->tokenizer, $string);

			return collect($tokens);
		}

		return Str::of($string)
			->lower()
			->matchAll('/[[:alpha:]]+/u');
	}

	/**
	 * @return $this
	 */
	public function learn(string $statement, string $type): self
	{
		foreach ($this->tokenize($statement) as $word) {
			$this->incrementWord($type, $word);
		}

		$this->incrementType($type);

		return $this;
	}

	/**
	 * @return Collection<string, string>
	 */
	public function guess(string $statement): Collection
	{
		$words = $this->tokenize($statement);

		return collect($this->documents)
			->map(function ($count, string $type) use ($words) {
				$likelihood = $this->pTotal($type);

				foreach ($words as $word) {
					$likelihood *= $this->p($word, $type);
				}

				return (string) BigDecimal::of($likelihood);
			})
			->sortDesc();
	}

	public function most(string $statement): string
	{
		/** @var string */
		return $this->guess($statement)->keys()->first();
	}

	/**
	 * @return self
	 */
	public function uneven(bool $enabled = false): self
	{
		$this->uneven = $enabled;

		return $this;
	}

	/**
	 * Increment the document count for the type
	 */
	private function incrementType(string $type): void
	{
		if (! isset($this->documents[$type])) {
			$this->documents[$type] = 0;
		}

		$this->documents[$type]++;
	}

	/**
	 * Increment the word count for the given type
	 */
	private function incrementWord(string $type, string $word): void
	{
		$ignored = config('autospam.ignored_tokens');
		if(!$ignored) {
			$ignored = ['the', 'a', 'of', 'and'];
		} else {
			$ignored = explode(',', $ignored);
		}
		if ($type == 'spam' && in_array($word, $ignored)) {
			return;
		}
		if (! isset($this->words[$type][$word])) {
			$this->words[$type][$word] = 0;
		}

		$this->words[$type][$word]++;
	}

	/**
	 * @return float|int
	 */
	private function p(string $word, string $type)
	{
		$count = $this->words[$type][$word] ?? 0;

		return ($count + 1) / (array_sum($this->words[$type]) + 1);
	}

	/**
	 * @return float|int
	 */
	private function pTotal(string $type)
	{
		return $this->uneven
			? ($this->documents[$type] + 1) / (array_sum($this->documents) + 1)
			: 1;
	}

	public function export()
	{
		$words = $this->words;
		$words = collect($words)
			->map(function($w) {
				arsort($w);
				return $w;
			})
			->all();
		return json_encode([
			'_ns' => 'https://pixelfed.org/ns/nlp',
			'_v' => '1.0',
			'documents' => $this->documents,
			'words' => $words
		], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function import($documents, $words)
	{
		$this->documents = $documents;
		$this->words = $words;
	}
}
