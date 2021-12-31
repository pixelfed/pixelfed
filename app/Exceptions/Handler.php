<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use League\OAuth2\Server\Exception\OAuthServerException;

class Handler extends ExceptionHandler
{
	/**
	 * A list of the exception types that are not reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		OAuthServerException::class,
		\Zttp\ConnectionException::class,
		\GuzzleHttp\Exception\ConnectException::class,
		\Illuminate\Http\Client\ConnectionException::class
	];

	/**
	 * A list of the inputs that are never flashed for validation exceptions.
	 *
	 * @var array
	 */
	protected $dontFlash = [
		'password',
		'password_confirmation',
	];

	/**
	 * Report or log an exception.
	 *
	 * @param \Exception $exception
	 *
	 * @return void
	 */
	public function report(Throwable $exception)
	{
		parent::report($exception);
	}

	/**
	* Register the exception handling callbacks for the application.
	*
	* @return void
	*/
	public function register()
	{
		$this->reportable(function (\BadMethodCallException $e) {
			return app()->environment() !== 'production';
		});

		$this->reportable(function (\Illuminate\Http\Client\ConnectionException $e) {
			return app()->environment() !== 'production';
		});
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Exception               $exception
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Throwable $exception)
	{
		if ($request->wantsJson())
			return response()->json(
				['error' => $exception->getMessage()],
				method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500
			);
		return parent::render($request, $exception);
	}
}
